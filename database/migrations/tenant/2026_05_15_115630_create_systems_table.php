<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Notifications (in-app) ============ */
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');                          // notification class
            $table->morphs('notifiable');                    // user
            $table->text('data');
            $table->string('title')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->default('#6C7DF7');
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        /* ============ Tenant-level Audit Logs ============ */
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');                         // "employee.created", "payroll.approved"
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
        });

        /* ============ Settings (key-value tenant config) ============ */
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('group')->default('general');     // general, payroll, attendance, etc.
            $table->string('type')->default('string');       // string, integer, boolean, json
            $table->boolean('is_public')->default(false);    // accessible by employees?
            $table->timestamps();
        });

        /* ============ Custom Fields (per-module dynamic fields) ============ */
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('module');                         // "employee", "asset", etc.
            $table->string('label');
            $table->string('field_key');
            $table->enum('type', ['text', 'textarea', 'number', 'date', 'select', 'multiselect', 'checkbox', 'file', 'url'])->default('text');
            $table->json('options')->nullable();              // for select/multiselect
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['module', 'field_key']);
        });

        /* ============ Email Templates ============ */
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->longText('body');
            $table->string('category')->nullable();
            $table->json('variables')->nullable();            // available placeholders
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
    }
};