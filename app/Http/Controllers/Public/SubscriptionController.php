<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantJob;
use App\Models\Main\Payment;
use App\Models\Main\Subscription;
use App\Models\Main\SubscriptionPlan;
use App\Models\Main\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class SubscriptionController extends Controller
{
    /* ====================================================================
     | CHECKOUT — public pricing page → Stripe Checkout (new tenants)
     |====================================================================*/
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_slug'    => 'required|string|exists:main.subscription_plans,slug',
            'billing_cycle'=> 'required|in:monthly,yearly',
            'company_name' => 'required|string|max:100',
            'contact_name' => 'required|string|max:100',
            'email'        => 'required|email|unique:main.tenants,contact_email',
        ]);

        $plan = SubscriptionPlan::where('slug', $request->plan_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $priceId = $request->billing_cycle === 'yearly'
            ? $plan->stripe_yearly_price_id
            : $plan->stripe_monthly_price_id;

        if (! $priceId) {
            return back()->with('error', 'This plan is not yet configured for payments. Please contact support.');
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $signupData = [
            'plan_slug'     => $plan->slug,
            'billing_cycle' => $request->billing_cycle,
            'company_name'  => $request->company_name,
            'contact_name'  => $request->contact_name,
            'email'         => $request->email,
            'context'       => 'new_signup',
        ];
        session(['stripe_signup' => $signupData]);

        $session = $stripe->checkout->sessions->create([
            'mode'                  => 'subscription',
            'payment_method_types'  => ['card'],
            'line_items'            => [[
                'price'    => $priceId,
                'quantity' => 1,
            ]],
            'subscription_data'     => [
                'trial_period_days' => $plan->trial_days ?: null,
                'metadata'          => $signupData,
            ],
            'customer_email'        => $request->email,
            'success_url'           => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'            => route('home') . '#pricing',
            'metadata'              => $signupData,
            'allow_promotion_codes' => true,
        ]);

        return redirect($session->url);
    }

    /* ====================================================================
     | SUCCESS PAGE
     | Stripe redirects here after the user completes checkout.
     | The webhook (checkout.session.completed) may fire before or after
     | this page loads. We handle both cases:
     |  - Tenant already provisioned → show "ready" state
     |  - Tenant not yet provisioned → provision synchronously here
     |====================================================================*/
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return redirect()->route('home')->with('error', 'Invalid checkout session.');
        }

        $stripe          = new StripeClient(config('services.stripe.secret'));
        $checkoutSession = $stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        $email  = $checkoutSession->customer_email;
        $tenant = Tenant::where('contact_email', $email)->first();

        // Tenant not provisioned yet — do it synchronously here as a fallback.
        // This covers: slow webhook delivery, QUEUE_CONNECTION=database with no worker, etc.
        if (! $tenant && $email) {
            $meta = (array) $checkoutSession->metadata;

            $plan = SubscriptionPlan::where('slug', $meta['plan_slug'] ?? '')->first();

            if ($plan) {
                $companyName = $meta['company_name'] ?? 'Company';
                $slug        = $this->generateUniqueSlug($companyName);

                $tenant = Tenant::create([
                    'company_name'       => $companyName,
                    'slug'               => $slug,
                    'contact_name'       => $meta['contact_name'] ?? '',
                    'contact_email'      => $email,
                    'database_name'      => Tenant::generateDatabaseName($slug),
                    'status'             => 'trial',
                    'trial_ends_at'      => now()->addDays($plan->trial_days),
                    'stripe_customer_id' => $checkoutSession->customer,
                    'country'            => 'US',
                    'timezone'           => 'America/New_York',
                ]);

                $subId = is_string($checkoutSession->subscription)
                    ? $checkoutSession->subscription
                    : ($checkoutSession->subscription?->id ?? null);

                if ($subId) {
                    $this->createOrUpdateSubscription($tenant, $plan, $subId, $meta['billing_cycle'] ?? 'monthly');
                }

                $tenant->applyPlan($plan);

                // Run provisioning synchronously (no queue needed)
                try {
                    \App\Jobs\ProvisionTenantJob::dispatchSync(
                        $tenant,
                        $meta['contact_name'] ?? 'Admin'
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Sync provisioning failed on success page: ' . $e->getMessage());
                    // Don't crash — tenant record exists, admin can re-provision manually
                }

                $tenant = $tenant->fresh();
            }
        }

        return view('public.checkout-success', compact('tenant', 'email'));
    }

    /* ====================================================================
     | WEBHOOK — Stripe event handler
     |====================================================================*/
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received: ' . $event->type);

        match ($event->type) {
            'checkout.session.completed'      => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.created'   => $this->handleSubscriptionCreated($event->data->object),
            'customer.subscription.updated'   => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted'   => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.payment_succeeded'       => $this->handlePaymentSucceeded($event->data->object),
            'invoice.payment_failed'          => $this->handlePaymentFailed($event->data->object),
            default                           => null,
        };

        return response()->json(['received' => true]);
    }

    /* ====================================================================
     | checkout.session.completed
     | Fired after a successful Stripe Checkout session (new tenant OR
     | admin upgrading an existing tenant for the first time).
     |====================================================================*/
    protected function handleCheckoutCompleted(object $session): void
    {
        $meta = (array) $session->metadata;

        $email   = $meta['email'] ?? $session->customer_email;
        $context = $meta['context'] ?? 'new_signup';

        /* ── Admin upgrade context (existing tenant, first card entry) ── */
        if ($context === 'admin_upgrade' && ! empty($meta['tenant_id'])) {
            $tenant = Tenant::find((int) $meta['tenant_id']);
            if (! $tenant) return;

            // Save Stripe customer ID if new
            if (! $tenant->stripe_customer_id) {
                $tenant->update(['stripe_customer_id' => $session->customer]);
            }

            $plan = SubscriptionPlan::where('slug', $meta['plan_slug'] ?? '')->first();
            if ($plan) {
                $this->createOrUpdateSubscription($tenant, $plan, $session->subscription, $meta['billing_cycle'] ?? 'monthly');
                $tenant->applyPlan($plan);
            }

            Log::info("Admin upgrade checkout completed for tenant {$tenant->slug}");
            return;
        }

        /* ── New signup context ── */
        if (! $email) {
            Log::error('Checkout completed but no email found', ['session' => $session->id]);
            return;
        }

        // Prevent double-provisioning
        if (Tenant::where('contact_email', $email)->exists()) {
            Log::info("Tenant already exists for {$email}, skipping provisioning.");
            return;
        }

        $plan = SubscriptionPlan::where('slug', $meta['plan_slug'] ?? '')->first();
        if (! $plan) {
            Log::error('Plan not found: ' . ($meta['plan_slug'] ?? 'unknown'));
            return;
        }

        $companyName = $meta['company_name'] ?? 'Company';
        $slug        = $this->generateUniqueSlug($companyName);

        $tenant = Tenant::create([
            'company_name'       => $companyName,
            'slug'               => $slug,
            'contact_name'       => $meta['contact_name'] ?? '',
            'contact_email'      => $email,
            'database_name'      => Tenant::generateDatabaseName($slug),
            'status'             => 'trial',
            'trial_ends_at'      => now()->addDays($plan->trial_days),
            'stripe_customer_id' => $session->customer,
            'country'            => 'US',
            'timezone'           => 'America/New_York',
        ]);

        $this->createOrUpdateSubscription($tenant, $plan, $session->subscription, $meta['billing_cycle'] ?? 'monthly');
        $tenant->applyPlan($plan);

        ProvisionTenantJob::dispatch($tenant, $meta['contact_name'] ?? 'Admin')->onQueue('default');

        Log::info("Tenant provisioning dispatched for: {$email}");
    }

    /* ====================================================================
     | customer.subscription.created
     | Fired when a subscription is created (may fire before checkout.completed).
     | We use it to ensure the subscription record exists in our DB.
     |====================================================================*/
    protected function handleSubscriptionCreated(object $subscription): void
    {
        $tenant = Tenant::where('stripe_customer_id', $subscription->customer)->first();
        if (! $tenant) return;

        $existing = Subscription::where('stripe_subscription_id', $subscription->id)->first();
        if ($existing) return; // already created by checkout.completed handler

        // Resolve plan from Stripe price metadata or subscription metadata
        $meta     = (array) ($subscription->metadata ?? []);
        $planSlug = $meta['plan_slug'] ?? null;
        $plan     = $planSlug ? SubscriptionPlan::where('slug', $planSlug)->first() : null;

        if (! $plan) {
            // Try resolving from price ID
            $priceId = $subscription->items->data[0]->price->id ?? null;
            if ($priceId) {
                $plan = SubscriptionPlan::where('stripe_monthly_price_id', $priceId)
                    ->orWhere('stripe_yearly_price_id', $priceId)
                    ->first();
            }
        }

        if (! $plan) return;

        $billing = $meta['billing_cycle'] ?? 'monthly';
        $this->createOrUpdateSubscription($tenant, $plan, $subscription->id, $billing, $subscription);
    }

    /* ====================================================================
     | customer.subscription.updated
     | Plan change, renewal, trial expiry, cancellation flag.
     |====================================================================*/
    protected function handleSubscriptionUpdated(object $subscription): void
    {
        $tenant = Tenant::where('stripe_customer_id', $subscription->customer)->first();
        if (! $tenant) return;

        $sub = Subscription::where('stripe_subscription_id', $subscription->id)->first();
        if (! $sub) return;

        $statusMap = [
            'active'    => 'active',
            'trialing'  => 'trialing',
            'past_due'  => 'past_due',
            'canceled'  => 'cancelled',
            'unpaid'    => 'unpaid',
            'incomplete'=> 'incomplete',
        ];

        $newStatus = $statusMap[$subscription->status] ?? $subscription->status;

        $sub->update([
            'status'                   => $newStatus,
            'cancel_at_period_end'     => (bool) $subscription->cancel_at_period_end,
            'current_period_starts_at' => \Carbon\Carbon::createFromTimestamp($subscription->current_period_start),
            'current_period_ends_at'   => \Carbon\Carbon::createFromTimestamp($subscription->current_period_end),
        ]);

        // Detect plan change (item price ID changed)
        $newPriceId = $subscription->items->data[0]->price->id ?? null;
        if ($newPriceId) {
            $newPlan = SubscriptionPlan::where('stripe_monthly_price_id', $newPriceId)
                ->orWhere('stripe_yearly_price_id', $newPriceId)
                ->first();

            if ($newPlan && $newPlan->id !== $sub->plan_id) {
                $sub->update(['plan_id' => $newPlan->id]);
                $tenant->applyPlan($newPlan);
                Log::info("Tenant {$tenant->slug} upgraded to plan {$newPlan->name}");
            }
        }

        $tenantStatus = match($subscription->status) {
            'active'    => 'active',
            'trialing'  => 'trial',
            'past_due'  => 'past_due',
            'canceled'  => 'cancelled',
            default     => $tenant->status,
        };

        $tenant->update(['status' => $tenantStatus]);

        Log::info("Subscription updated for tenant {$tenant->slug}: {$subscription->status}");
    }

    /* ====================================================================
     | customer.subscription.deleted
     |====================================================================*/
    protected function handleSubscriptionDeleted(object $subscription): void
    {
        $tenant = Tenant::where('stripe_customer_id', $subscription->customer)->first();
        if (! $tenant) return;

        $tenant->update(['status' => 'cancelled']);

        Subscription::where('stripe_subscription_id', $subscription->id)->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'ends_at'      => now(),
        ]);

        Log::info("Subscription cancelled for tenant: {$tenant->slug}");
    }

    /* ====================================================================
     | invoice.payment_succeeded
     |====================================================================*/
    protected function handlePaymentSucceeded(object $invoice): void
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();
        if (! $tenant) return;

        // Avoid duplicates
        if (Payment::where('stripe_invoice_id', $invoice->id)->exists()) return;

        Payment::create([
            'tenant_id'         => $tenant->id,
            'stripe_invoice_id' => $invoice->id,
            'amount'            => $invoice->amount_paid / 100,
            'currency'          => strtoupper($invoice->currency),
            'status'            => 'succeeded',
            'paid_at'           => now(),
        ]);

        // If tenant was past_due, restore to active
        if ($tenant->status === 'past_due') {
            $tenant->update(['status' => 'active']);
        }

        Log::info("Payment succeeded for tenant: {$tenant->slug} — \${$invoice->amount_paid}");
    }

    /* ====================================================================
     | invoice.payment_failed
     |====================================================================*/
    protected function handlePaymentFailed(object $invoice): void
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();
        if (! $tenant) return;

        if (Payment::where('stripe_invoice_id', $invoice->id)->where('status', 'failed')->exists()) return;

        $tenant->update(['status' => 'past_due']);

        Payment::create([
            'tenant_id'         => $tenant->id,
            'stripe_invoice_id' => $invoice->id,
            'amount'            => $invoice->amount_due / 100,
            'currency'          => strtoupper($invoice->currency),
            'status'            => 'failed',
            'failure_message'   => 'Payment failed — card declined or insufficient funds',
        ]);

        Log::warning("Payment failed for tenant: {$tenant->slug}");
    }

    /* ====================================================================
     | Helpers
     |====================================================================*/

    /**
     * Create a new Subscription record or update the existing one for a tenant.
     */
    protected function createOrUpdateSubscription(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $stripeSubId,
        string $billingCycle,
        ?object $stripeSubscription = null
    ): Subscription {
        $trialDays = $plan->trial_days ?? 0;

        $data = [
            'tenant_id'                => $tenant->id,
            'plan_id'                  => $plan->id,
            'stripe_subscription_id'   => $stripeSubId,
            'billing_cycle'            => $billingCycle,
            'amount'                   => $billingCycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
            'status'                   => $stripeSubscription ? ($stripeSubscription->status === 'trialing' ? 'trialing' : 'active') : ($trialDays > 0 ? 'trialing' : 'active'),
            'trial_starts_at'          => $trialDays > 0 ? now() : null,
            'trial_ends_at'            => $trialDays > 0 ? now()->addDays($trialDays) : null,
            'current_period_starts_at' => now(),
            'current_period_ends_at'   => $trialDays > 0 ? now()->addDays($trialDays) : now()->addMonth(),
        ];

        return Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSubId],
            $data
        );
    }

    protected function generateUniqueSlug(string $companyName): string
    {
        $base = Str::slug($companyName);
        $slug = $base;
        $i    = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
