<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit Logs — system-level audit trail for SaaS admin actions.
     * (Each tenant DB also has its own audit_logs table for in-tenant actions.)
     */
    public function up(): void
    {
        Schema::connection('main')->create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();

            $table->string('event');           // e.g. "tenant.created", "subscription.cancelled"
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('event');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('audit_logs');
    }
};