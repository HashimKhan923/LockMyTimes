<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // Travel, Meals, Equipment, etc.
            $table->string('code', 20)->nullable();
            $table->string('icon')->nullable();
            $table->decimal('monthly_limit', 12, 2)->nullable();
            $table->decimal('per_expense_limit', 12, 2)->nullable();
            $table->boolean('requires_receipt')->default(true);
            $table->decimal('receipt_required_above', 12, 2)->default(25.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('expense_categories');
            $table->string('expense_number')->unique();   // "EXP-2025-0001"
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('expense_date');
            $table->string('merchant')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('project_code')->nullable();
            $table->string('receipt_path')->nullable();
            $table->boolean('is_mileage')->default(false);
            $table->decimal('miles', 8, 2)->nullable();
            $table->decimal('mileage_rate', 6, 4)->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'paid', 'cancelled'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_via_payslip_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->integer('approval_level')->default(1);
            $table->enum('decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_approvals');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};