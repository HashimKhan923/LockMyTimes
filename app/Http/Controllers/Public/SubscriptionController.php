<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantJob;
use App\Models\Main\Payment;
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
     | CHECKOUT — redirect to Stripe Checkout page
     |====================================================================*/

    public function checkout(Request $request)
    {
        $request->validate([
            'plan_slug'    => 'required|string|exists:subscription_plans,slug',
            'billing_cycle'=> 'required|in:monthly,yearly',
            'company_name' => 'required|string|max:100',
            'contact_name' => 'required|string|max:100',
            'email'        => 'required|email|unique:tenants,contact_email',
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

        // Store signup data in session so we can use it after checkout
        $signupData = [
            'plan_slug'     => $plan->slug,
            'billing_cycle' => $request->billing_cycle,
            'company_name'  => $request->company_name,
            'contact_name'  => $request->contact_name,
            'email'         => $request->email,
        ];
        session(['stripe_signup' => $signupData]);

        $session = $stripe->checkout->sessions->create([
            'mode'                 => 'subscription',
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price'    => $priceId,
                'quantity' => 1,
            ]],
            'subscription_data' => [
                'trial_period_days' => $plan->trial_days,
                'metadata'          => $signupData,
            ],
            'customer_email'   => $request->email,
            'success_url'      => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'       => route('home').'#pricing',
            'metadata'         => $signupData,
            'allow_promotion_codes' => true,
        ]);

        return redirect($session->url);
    }

    /* ====================================================================
     | SUCCESS — user returns from Stripe after entering card details
     |====================================================================*/

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return redirect()->route('home')->with('error', 'Invalid checkout session.');
        }

        $stripe        = new StripeClient(config('services.stripe.secret'));
        $checkoutSession = $stripe->checkout->sessions->retrieve($sessionId);

        // Look up the tenant by email (may already be provisioned by webhook)
        $email  = $checkoutSession->customer_email;
        $tenant = Tenant::where('contact_email', $email)->first();

        return view('public.checkout-success', compact('tenant', 'email'));
    }

    /* ====================================================================
     | WEBHOOK — Stripe calls this endpoint on payment/subscription events
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

        Log::info('Stripe webhook received: '.$event->type);

        match ($event->type) {
            'checkout.session.completed'      => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.updated'   => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted'   => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.payment_failed'          => $this->handlePaymentFailed($event->data->object),
            'invoice.payment_succeeded'       => $this->handlePaymentSucceeded($event->data->object),
            default                           => null,
        };

        return response()->json(['received' => true]);
    }

    /* ====================================================================
     | Webhook Handlers
     |====================================================================*/

    protected function handleCheckoutCompleted(object $session): void
    {
        $meta = (array) $session->metadata;

        if (empty($meta['email']) && ! empty($session->customer_email)) {
            $meta['email'] = $session->customer_email;
        }

        $email = $meta['email'] ?? $session->customer_email;

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
            Log::error("Plan not found: ".($meta['plan_slug'] ?? 'unknown'));
            return;
        }

        // Generate a unique slug from company name
        $companyName = $meta['company_name'] ?? 'Company';
        $slug        = $this->generateUniqueSlug($companyName);

        // Create the tenant record
        $tenant = Tenant::create([
            'company_name'  => $companyName,
            'slug'          => $slug,
            'contact_name'  => $meta['contact_name'] ?? '',
            'contact_email' => $email,
            'database_name' => Tenant::generateDatabaseName($slug),
            'status'        => 'trial',
            'trial_ends_at' => now()->addDays($plan->trial_days),
            'stripe_customer_id' => $session->customer,
            'country'       => 'US',
            'timezone'      => 'America/New_York',
        ]);

        // Create subscription record
        \App\Models\Main\Subscription::create([
            'tenant_id'                => $tenant->id,
            'plan_id'                  => $plan->id,
            'stripe_subscription_id'   => $session->subscription,
            'billing_cycle'            => $meta['billing_cycle'] ?? 'monthly',
            'amount'                   => $meta['billing_cycle'] === 'yearly'
                                            ? $plan->yearly_price
                                            : $plan->monthly_price,
            'status'                   => 'trialing',
            'trial_starts_at'          => now(),
            'trial_ends_at'            => $tenant->trial_ends_at,
            'current_period_starts_at' => now(),
            'current_period_ends_at'   => $tenant->trial_ends_at,
        ]);

        // Apply plan feature flags and limits to the tenant record
        $tenant->applyPlan($plan);

        // Dispatch background job to provision the tenant DB (generates a secure random password)
        ProvisionTenantJob::dispatch($tenant, $meta['contact_name'] ?? 'Admin')
            ->onQueue('default');

        Log::info("Tenant provisioning dispatched for: {$email}");
    }

    protected function handleSubscriptionUpdated(object $subscription): void
    {
        $tenant = Tenant::where('stripe_customer_id', $subscription->customer)->first();
        if (! $tenant) return;

        $sub = \App\Models\Main\Subscription::where('stripe_subscription_id', $subscription->id)->first();
        if (! $sub) return;

        $statusMap = [
            'active'   => 'active',
            'trialing' => 'trialing',
            'past_due' => 'past_due',
            'canceled' => 'cancelled',
            'unpaid'   => 'unpaid',
        ];

        $sub->update([
            'status'                   => $statusMap[$subscription->status] ?? $subscription->status,
            'current_period_starts_at' => now()->createFromTimestamp($subscription->current_period_start),
            'current_period_ends_at'   => now()->createFromTimestamp($subscription->current_period_end),
        ]);

        // Update tenant status
        $tenantStatus = match($subscription->status) {
            'active'   => 'active',
            'trialing' => 'trial',
            'past_due' => 'past_due',
            'canceled' => 'cancelled',
            default    => $tenant->status,
        };

        $tenant->update(['status' => $tenantStatus]);

        Log::info("Subscription updated for tenant {$tenant->slug}: {$subscription->status}");
    }

    protected function handleSubscriptionDeleted(object $subscription): void
    {
        $tenant = Tenant::where('stripe_customer_id', $subscription->customer)->first();
        if (! $tenant) return;

        $tenant->update(['status' => 'cancelled']);

        \App\Models\Main\Subscription::where('stripe_subscription_id', $subscription->id)
            ->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
                'ends_at'      => now(),
            ]);

        Log::info("Subscription cancelled for tenant: {$tenant->slug}");
    }

    protected function handlePaymentFailed(object $invoice): void
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();
        if (! $tenant) return;

        $tenant->update(['status' => 'past_due']);

        // Record failed payment
        Payment::create([
            'tenant_id'               => $tenant->id,
            'stripe_invoice_id'       => $invoice->id,
            'amount'                  => $invoice->amount_due / 100,
            'currency'                => strtoupper($invoice->currency),
            'status'                  => 'failed',
            'failure_message'         => 'Payment failed',
        ]);

        Log::warning("Payment failed for tenant: {$tenant->slug}");
    }

    protected function handlePaymentSucceeded(object $invoice): void
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();
        if (! $tenant) return;

        Payment::create([
            'tenant_id'         => $tenant->id,
            'stripe_invoice_id' => $invoice->id,
            'amount'            => $invoice->amount_paid / 100,
            'currency'          => strtoupper($invoice->currency),
            'status'            => 'succeeded',
            'paid_at'           => now(),
        ]);

        Log::info("Payment succeeded for tenant: {$tenant->slug}");
    }

    /* ====================================================================
     | Helpers
     |====================================================================*/

    protected function generateUniqueSlug(string $companyName): string
    {
        $base = Str::slug($companyName);
        $slug = $base;
        $i    = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}