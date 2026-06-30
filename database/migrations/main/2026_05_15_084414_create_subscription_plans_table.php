<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscription Plans — the pricing tiers offered on the marketing site.
     * Examples: Starter, Professional, Enterprise.
     */
    public function up(): void
    {
        Schema::connection('main')->create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // "Starter", "Professional", "Enterprise"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('badge')->nullable();    // "Most Popular", "Best Value"

            // Pricing
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Stripe IDs
            $table->string('stripe_monthly_price_id')->nullable();
            $table->string('stripe_yearly_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();

            // Limits
            $table->integer('max_employees')->default(10);
            $table->integer('max_storage_gb')->default(5);
            $table->integer('max_admins')->default(2);

            // Features (JSON — full list of features for this plan)
            $table->json('features')->nullable();

            // Module access toggles
            $table->boolean('has_attendance')->default(true);
            $table->boolean('has_payroll')->default(true);
            $table->boolean('has_performance')->default(true);
            $table->boolean('has_assets')->default(true);
            $table->boolean('has_training')->default(true);
            $table->boolean('has_recruitment')->default(false);
            $table->boolean('has_documents')->default(true);
            $table->boolean('has_expenses')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_white_label')->default(false);
            $table->boolean('has_priority_support')->default(false);

            // Display & state
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('trial_days')->default(14);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('subscription_plans');
    }
};