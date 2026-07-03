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

        // If tenant already has an active Stripe subscription — create a portal session to switch
        $activeSub = Subscription::where('tenant_id', $currentTenant->id)
            ->whereIn('status', ['active', 'trialing'])
            ->whereNotNull('stripe_subscription_id')
            ->latest()
            ->first();

        if ($activeSub && $currentTenant->stripe_customer_id) {
            // Use Stripe Customer Portal for plan changes on existing subscriptions
            $session = $stripe->billingPortal->sessions->create([
                'customer'   => $currentTenant->stripe_customer_id,
                'return_url' => route('admin.billing.index', $tenant),
            ]);
            return redirect($session->url);
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
