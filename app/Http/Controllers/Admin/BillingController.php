<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Main\Subscription;
use App\Models\Main\SubscriptionPlan;
use App\Models\Main\Tenant;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class BillingController extends Controller
{
    public function __construct(protected TenantManager $tenantManager) {}

    /* ================================================================
     | BILLING DASHBOARD — show current plan, subscription, invoices
     |================================================================*/
    public function index(string $tenant)
    {
        $currentTenant   = $this->tenantManager->current();
        $subscription    = Subscription::with('plan')
            ->where('tenant_id', $currentTenant->id)
            ->latest()
            ->first();
        $plans           = SubscriptionPlan::active()->ordered()->get();

        $invoices = [];
        if ($currentTenant->stripe_customer_id) {
            try {
                $stripe   = new StripeClient(config('services.stripe.secret'));
                $list     = $stripe->invoices->all([
                    'customer' => $currentTenant->stripe_customer_id,
                    'limit'    => 10,
                ]);
                $invoices = $list->data;
            } catch (\Throwable) {}
        }

        return view('admin.billing.index', compact(
            'tenant', 'currentTenant', 'subscription', 'plans', 'invoices'
        ));
    }

    /* ================================================================
     | PREVIEW CHANGE — what switching to this plan/cycle will cost today,
     | shown as a confirmation step before an existing subscriber actually
     | switches. First-time subscribers (no existing subscription) skip
     | this — Stripe Checkout itself is their payment confirmation step.
     |================================================================*/
    public function previewChange(Request $request, string $tenant)
    {
        $request->validate([
            'plan_slug'     => 'required|string|exists:main.subscription_plans,slug',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = SubscriptionPlan::where('slug', $request->plan_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $priceId = $request->billing_cycle === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (! $priceId) {
            return response()->json(['message' => 'This plan is not yet configured for payments.'], 422);
        }

        $currentTenant = $this->tenantManager->current();

        $activeSub = Subscription::where('tenant_id', $currentTenant->id)
            ->whereIn('status', ['active', 'trialing'])
            ->whereNotNull('stripe_subscription_id')
            ->latest()
            ->first();

        if (! $activeSub) {
            return response()->json(['has_existing_subscription' => false]);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            $stripeSub = $stripe->subscriptions->retrieve($activeSub->stripe_subscription_id);
            $itemId    = $stripeSub->items->data[0]->id ?? null;

            if (! $itemId) {
                return response()->json(['message' => 'Could not find your subscription details.'], 422);
            }

            $preview = $stripe->invoices->createPreview([
                'subscription' => $activeSub->stripe_subscription_id,
                'subscription_details' => [
                    'items' => [[
                        'id'    => $itemId,
                        'price' => $priceId,
                    ]],
                    'proration_behavior' => 'create_prorations',
                ],
            ]);

            return response()->json([
                'has_existing_subscription' => true,
                'amount_due'  => round($preview->amount_due / 100, 2),
                'currency'    => strtoupper($preview->currency),
                'is_trialing' => $activeSub->status === 'trialing',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not calculate the price change. Please try again.'], 422);
        }
    }

    /* ================================================================
     | CHECKOUT — start a new subscription or upgrade plan
     |================================================================*/
    public function checkout(Request $request, string $tenant)
    {
        $request->validate([
            'plan_slug'     => 'required|string|exists:main.subscription_plans,slug',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan   = SubscriptionPlan::where('slug', $request->plan_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $priceId = $request->billing_cycle === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (! $priceId) {
            return back()->with('error', 'This plan is not yet configured for payments. Please contact support.');
        }

        $currentTenant = $this->tenantManager->current();
        $stripe        = new StripeClient(config('services.stripe.secret'));

        // If tenant already has an active Stripe subscription, switch its price in place below
        // instead of starting a brand new Checkout session.
        $activeSub = Subscription::where('tenant_id', $currentTenant->id)
            ->whereIn('status', ['active', 'trialing'])
            ->whereNotNull('stripe_subscription_id')
            ->latest()
            ->first();

        if ($activeSub && $currentTenant->stripe_customer_id && $activeSub->stripe_subscription_id) {
            // Already subscribed — switch the existing Stripe subscription's price directly
            // rather than sending the admin to the generic billing portal (which has no way to
            // know which plan they meant to switch to).
            $stripeSub = $stripe->subscriptions->retrieve($activeSub->stripe_subscription_id);
            $itemId    = $stripeSub->items->data[0]->id ?? null;

            if (! $itemId) {
                return back()->with('error', 'Could not find your subscription details. Please contact support.');
            }

            $stripe->subscriptions->update($activeSub->stripe_subscription_id, [
                'items' => [[
                    'id'    => $itemId,
                    'price' => $priceId,
                ]],
                'proration_behavior' => 'create_prorations',
                'metadata' => [
                    'tenant_id'     => (string) $currentTenant->id,
                    'tenant_slug'   => $currentTenant->slug,
                    'plan_slug'     => $plan->slug,
                    'billing_cycle' => $request->billing_cycle,
                ],
            ]);

            // Update our own records immediately rather than waiting on the
            // customer.subscription.updated webhook — this app has no reachable public
            // endpoint for Stripe to call back to in most dev/local setups, so relying on the
            // webhook alone left the plan change invisible even though Stripe had it right.
            // (If the webhook does arrive later, handleSubscriptionUpdated() just no-ops since
            // the plan_id will already match.)
            $activeSub->update([
                'plan_id'       => $plan->id,
                'stripe_price_id' => $priceId,
                'billing_cycle' => $request->billing_cycle,
                'amount'        => $request->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
            ]);
            $currentTenant->applyPlan($plan);

            return redirect()->route('admin.billing.index', $tenant)
                ->with('success', "Your plan has been changed to {$plan->name}.");
        }

        // First-time checkout for this tenant
        $sessionParams = [
            'mode'                 => 'subscription',
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price'    => $priceId,
                'quantity' => 1,
            ]],
            'subscription_data'    => [
                'trial_period_days' => $plan->trial_days ?: null,
                'metadata'          => [
                    'tenant_id'     => $currentTenant->id,
                    'tenant_slug'   => $currentTenant->slug,
                    'plan_slug'     => $plan->slug,
                    'billing_cycle' => $request->billing_cycle,
                    'context'       => 'admin_upgrade',
                ],
            ],
            'customer_email'       => $currentTenant->contact_email,
            'success_url'          => route('admin.billing.index', $tenant) . '?upgraded=1',
            'cancel_url'           => route('admin.billing.index', $tenant),
            'allow_promotion_codes'=> true,
            'metadata'             => [
                'tenant_id'     => (string) $currentTenant->id,
                'tenant_slug'   => $currentTenant->slug,
                'plan_slug'     => $plan->slug,
                'billing_cycle' => $request->billing_cycle,
                'context'       => 'admin_upgrade',
            ],
        ];

        // Pre-fill existing Stripe customer if we have one
        if ($currentTenant->stripe_customer_id) {
            unset($sessionParams['customer_email']);
            $sessionParams['customer'] = $currentTenant->stripe_customer_id;
        }

        $session = $stripe->checkout->sessions->create($sessionParams);

        return redirect($session->url);
    }

    /* ================================================================
     | BILLING PORTAL — manage payment methods, cancel, download invoices
     |================================================================*/
    public function portal(string $tenant)
    {
        $currentTenant = $this->tenantManager->current();

        if (! $currentTenant->stripe_customer_id) {
            return back()->with('error', 'No Stripe customer found. Please subscribe to a plan first.');
        }

        $stripe  = new StripeClient(config('services.stripe.secret'));
        $session = $stripe->billingPortal->sessions->create([
            'customer'   => $currentTenant->stripe_customer_id,
            'return_url' => route('admin.billing.index', $tenant),
        ]);

        return redirect($session->url);
    }

    /* ================================================================
     | CANCEL — mark cancel_at_period_end = true in Stripe
     |================================================================*/
    public function cancel(string $tenant)
    {
        $currentTenant = $this->tenantManager->current();
        $sub           = Subscription::where('tenant_id', $currentTenant->id)
            ->whereIn('status', ['active', 'trialing'])
            ->whereNotNull('stripe_subscription_id')
            ->latest()
            ->first();

        if (! $sub) {
            return back()->with('error', 'No active subscription found.');
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        $stripe->subscriptions->update($sub->stripe_subscription_id, [
            'cancel_at_period_end' => true,
        ]);

        $sub->update(['cancel_at_period_end' => true]);

        return back()->with('success', 'Your subscription will be cancelled at the end of the current billing period.');
    }

    /* ================================================================
     | RESUME — undo cancel_at_period_end
     |================================================================*/
    public function resume(string $tenant)
    {
        $currentTenant = $this->tenantManager->current();
        $sub           = Subscription::where('tenant_id', $currentTenant->id)
            ->where('cancel_at_period_end', true)
            ->whereNotNull('stripe_subscription_id')
            ->latest()
            ->first();

        if (! $sub) {
            return back()->with('error', 'No subscription scheduled for cancellation found.');
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        $stripe->subscriptions->update($sub->stripe_subscription_id, [
            'cancel_at_period_end' => false,
        ]);

        $sub->update(['cancel_at_period_end' => false]);

        return back()->with('success', 'Subscription renewal reinstated. You will continue to be billed normally.');
    }
}
