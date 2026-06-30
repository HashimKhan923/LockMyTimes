<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Clients (optional — for external/billable projects) ============ */
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->nullable()->unique();
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /* ============ Projects ============ */
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('project_manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('code', 30)->unique();              // "PRJ-2025-001"
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6C7DF7');
            $table->string('icon')->nullable();
            $table->string('cover_image')->nullable();

            // Type & visibility
            $table->enum('type', ['internal', 'client', 'product', 'campaign', 'maintenance', 'other'])->default('internal');
            $table->enum('visibility', ['public', 'private', 'members_only'])->default('members_only');
            $table->boolean('is_billable')->default(false);

            // Status
            $table->enum('status', [
                'planning',
                'active',
                'on_hold',
                'completed',
                'cancelled',
                'archived',
            ])->default('planning');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->integer('progress')->default(0);            // 0–100

            // Dates
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();

            // Budget & financials
            $table->decimal('budget_amount', 14, 2)->default(0);
            $table->string('budget_currency', 3)->default('USD');
            $table->decimal('budget_hours', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->nullable();   // default rate for this project
            $table->decimal('fixed_price', 14, 2)->nullable();
            $table->enum('billing_type', ['hourly', 'fixed', 'milestone', 'non_billable'])->default('non_billable');

            // Cached metrics (updated by jobs)
            $table->decimal('total_logged_hours', 10, 2)->default(0);
            $table->decimal('total_billable_hours', 10, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->integer('tasks_count')->default(0);
            $table->integer('completed_tasks_count')->default(0);
            $table->integer('members_count')->default(0);

            // Settings
            $table->json('custom_fields')->nullable();
            $table->json('settings')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index('due_date');
        });

        /* ============ Project Members ============ */
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('role', ['owner', 'manager', 'member', 'viewer'])->default('member');
            $table->decimal('hourly_rate', 10, 2)->nullable();   // override rate for this person on this project
            $table->boolean('can_log_time')->default(true);
            $table->boolean('can_manage_tasks')->default(false);
            $table->boolean('is_billable')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'employee_id']);
        });

        /* ============ Project Milestones ============ */
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['upcoming', 'in_progress', 'completed', 'overdue', 'cancelled'])->default('upcoming');
            $table->integer('progress')->default(0);
            $table->decimal('payment_amount', 14, 2)->nullable();   // for milestone-based billing
            $table->boolean('is_paid')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        /* ============ Project Documents ============ */
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('clients');
    }
};