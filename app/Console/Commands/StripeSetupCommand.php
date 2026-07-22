<?php

namespace App\Console\Commands;

use App\Models\Main\SubscriptionPlan;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class StripeSetupCommand extends Command
{
    protected $signature   = 'stripe:setup';
    protected $description = 'Create Stripe products and prices for all subscription plans.';

    public function handle(): int
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $plans = SubscriptionPlan::active()->ordered()->get();

        if ($plans->isEmpty()) {
            $this->error('No plans found. Run php artisan db:seed first.');
            return self::FAILURE;
        }

        foreach ($plans as $plan) {
            $this->info("Processing plan: {$plan->name}");

            // Create or retrieve Stripe product
            if ($plan->stripe_product_id) {
                $product = $stripe->products->retrieve($plan->stripe_product_id);
                $this->line(" Product exists: {$product->id}");
            } else {
                $product = $stripe->products->create([
                    'name'        => 'Lockmytimes '.$plan->name,
                    'description' => $plan->description,
                    'metadata'    => ['plan_slug' => $plan->slug],
                ]);
                $this->line("  + Created product: {$product->id}");
            }

            // Monthly price
            if ($plan->stripe_monthly_price_id) {
                $this->line(" Monthly price exists: {$plan->stripe_monthly_price_id}");
            } else {
                $monthly = $stripe->prices->create([
                    'product'       => $product->id,
                    'unit_amount'   => (int) ($plan->monthly_price * 100),
                    'currency'      => strtolower($plan->currency),
                    'recurring'     => ['interval' => 'month'],
                    'metadata'      => ['plan_slug' => $plan->slug, 'cycle' => 'monthly'],
                ]);
                $this->line("  + Created monthly price: {$monthly->id}");
                $plan->stripe_monthly_price_id = $monthly->id;
            }

            // Yearly price
            if ($plan->stripe_yearly_price_id) {
                $this->line(" Yearly price exists: {$plan->stripe_yearly_price_id}");
            } else {
                $yearly = $stripe->prices->create([
                    'product'       => $product->id,
                    'unit_amount'   => (int) ($plan->yearly_price * 100),
                    'currency'      => strtolower($plan->currency),
                    'recurring'     => ['interval' => 'year'],
                    'metadata'      => ['plan_slug' => $plan->slug, 'cycle' => 'yearly'],
                ]);
                $this->line("  + Created yearly price: {$yearly->id}");
                $plan->stripe_yearly_price_id = $yearly->id;
            }

            $plan->stripe_product_id = $product->id;
            $plan->save();

            $this->info(" Done: {$plan->name}");
        }

        $this->newLine();
        $this->info(' All Stripe products and prices are set up!');
        $this->line('Next: add your webhook endpoint in the Stripe dashboard:');
        $this->line('  URL: '.url('/stripe/webhook'));
        $this->line('  Events: checkout.session.completed, customer.subscription.updated,');
        $this->line('          customer.subscription.deleted, invoice.payment_failed');

        return self::SUCCESS;
    }
}