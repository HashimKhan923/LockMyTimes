<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Leave Types ============ */
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // Vacation, Sick, FMLA, etc.
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6C7DF7');
            $table->boolean('is_paid')->default(true);
            $table->decimal('default_days_per_year', 5, 2)->default(0);
            $table->decimal('max_carryover_days', 5, 2)->default(0);
            $table->enum('accrual_method', ['none', 'monthly', 'quarterly', 'annually', 'per_pay_period'])->default('none');
            $table->decimal('accrual_rate', 8, 4)->default(0);
            $table->integer('min_service_months')->default(0);   // months before eligible
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_documentation')->default(false);
            $table->integer('max_consecutive_days')->nullable();
            $table->integer('notice_days_required')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ============ Leave Balances (per employee, per type, per year) ============ */
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('allocated', 8, 2)->default(0);
            $table->decimal('accrued', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('pending', 8, 2)->default(0);
            $table->decimal('carried_over', 8, 2)->default(0);
            $table->decimal('adjusted', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        /* ============ Leave Requests ============ */
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types');
            $table->string('request_number')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 2);
            $table->enum('day_part', ['full_day', 'first_half', 'second_half'])->default('full_day');
            $table->text('reason');
            $table->text('contact_during_leave')->nullable();
            $table->json('attachments')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approver_comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        /* ============ Holidays ============ */
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // "Independence Day"
            $table->date('date');
            $table->enum('type', ['federal', 'state', 'company', 'religious'])->default('company');
            $table->string('state', 50)->nullable();
            $table->boolean('is_recurring')->default(false);     // recurs every year
            $table->boolean('is_paid')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};