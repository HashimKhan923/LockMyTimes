<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscriptions — links a tenant to a plan, mirrors Stripe subscription state.
     */
    public function up(): void
    {
        Schema::connection('main')->create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans');

            // Stripe identifiers
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_price_id')->nullable();

            // Cycle
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Status (mirrors Stripe statuses)
            $table->enum('status', [
                'trialing',
                'active',
                'past_due',
                'cancelled',
                'incomplete',
                'incomplete_expired',
                'unpaid',
                'paused',
            ])->default('trialing');

            // Lifecycle dates
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ends_at')->nullable();              // when subscription will end
            $table->boolean('cancel_at_period_end')->default(false);

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('subscriptions');
    }
};