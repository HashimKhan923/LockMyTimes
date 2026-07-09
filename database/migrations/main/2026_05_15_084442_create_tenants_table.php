<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenants — every company that subscribes to Lockmytimes.
     * Each tenant has its own dedicated database (database-per-tenant).
     */
    public function up(): void
    {
        Schema::connection('main')->create('tenants', function (Blueprint $table) {
            $table->id();

            // Company identity
            $table->string('company_name');
            $table->string('slug')->unique(); // URL slug /t/{slug}/...
            $table->string('subdomain')->nullable()->unique();  // optional future use
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('industry')->nullable();
            $table->integer('company_size')->nullable();  // approx employee count
            $table->string('website')->nullable();

            // Primary contact (the person who signed up)
            $table->string('contact_name');
            $table->string('contact_email')->unique();
            $table->string('contact_phone')->nullable();

            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 50)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('US');
            $table->string('timezone')->default('America/New_York');

            // Database details (per-tenant DB)
            $table->string('database_name')->unique();
            $table->boolean('database_provisioned')->default(false);

            // Stripe customer mapping
            $table->string('stripe_customer_id')->nullable()->unique();

            // Theme / branding (tenant can customize their own portal)
            $table->string('primary_color', 7)->default('#6C7DF7');
            $table->string('secondary_color', 7)->default('#FFB547');

            // Status
            $table->enum('status', ['pending', 'trial', 'active', 'past_due', 'suspended', 'cancelled'])->default('pending');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            // Onboarding
            $table->boolean('is_onboarded')->default(false);
            $table->json('onboarding_steps')->nullable();

            // Counts (cached for dashboard performance)
            $table->integer('employees_count')->default(0);
            $table->integer('admins_count')->default(0);
            $table->bigInteger('storage_used_bytes')->default(0);

            // Metadata
            $table->json('settings')->nullable();
            $table->json('feature_flags')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('tenants');
    }
};