<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments — every payment attempt (charge / refund) made by a tenant.
     */
    public function up(): void
    {
        Schema::connection('main')->create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();

            // Stripe references
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();

            // Money
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_refunded', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('tax_amount', 10, 2)->default(0);

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'succeeded',
                'failed',
                'refunded',
                'partially_refunded',
                'cancelled',
            ])->default('pending');

            // Payment method
            $table->string('payment_method')->nullable();   // card, ach, etc.
            $table->string('card_brand')->nullable();
            $table->string('card_last4', 4)->nullable();

            // Result info
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->text('receipt_url')->nullable();
            $table->string('description')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('payments');
    }
};