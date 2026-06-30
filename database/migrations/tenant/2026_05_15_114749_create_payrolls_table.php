<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Salary Components (earnings & deductions definitions) ============ */
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                // "Base Pay", "Overtime", "Health Insurance"
            $table->string('code', 30)->unique();
            $table->enum('type', ['earning', 'deduction', 'reimbursement', 'tax']);
            $table->enum('calculation', ['fixed', 'percentage', 'formula', 'hours_x_rate'])->default('fixed');
            $table->decimal('default_value', 12, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('shows_on_payslip')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ============ Employee Salary Components (per-employee assignments) ============ */
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        /* ============ Tax Settings (per state/year) ============ */
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('state', 50)->nullable();      // null = federal
            $table->enum('tax_type', ['federal_income', 'state_income', 'fica_ss', 'fica_medicare', 'futa', 'suta', 'sdi', 'local'])->default('federal_income');
            $table->json('brackets')->nullable();          // array of brackets with rate + min + max
            $table->decimal('flat_rate', 6, 4)->nullable();
            $table->decimal('wage_base', 12, 2)->nullable();   // cap for SS, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ============ Payroll Runs ============ */
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_number')->unique();           // "PR-2025-001"
            $table->enum('pay_schedule', ['weekly', 'bi_weekly', 'semi_monthly', 'monthly'])->default('bi_weekly');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->integer('total_employees')->default(0);
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_taxes', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /* ============ Payslips ============ */
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('payslip_number')->unique();

            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');

            // Hours
            $table->decimal('regular_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('holiday_hours', 8, 2)->default(0);
            $table->decimal('leave_hours', 8, 2)->default(0);

            // Earnings
            $table->decimal('base_pay', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0);
            $table->decimal('reimbursement', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);

            // Taxes
            $table->decimal('federal_tax', 12, 2)->default(0);
            $table->decimal('state_tax', 12, 2)->default(0);
            $table->decimal('local_tax', 12, 2)->default(0);
            $table->decimal('fica_ss', 12, 2)->default(0);
            $table->decimal('fica_medicare', 12, 2)->default(0);

            // Deductions
            $table->decimal('health_insurance', 12, 2)->default(0);
            $table->decimal('retirement_401k', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            // Final
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->decimal('ytd_gross', 12, 2)->default(0);
            $table->decimal('ytd_net', 12, 2)->default(0);
            $table->decimal('ytd_taxes', 12, 2)->default(0);

            // Status
            $table->enum('status', ['draft', 'finalized', 'paid', 'cancelled'])->default('draft');
            $table->enum('payment_method', ['direct_deposit', 'check', 'cash', 'other'])->default('direct_deposit');
            $table->string('pdf_path')->nullable();
            $table->boolean('is_emailed')->default(false);
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
        });

        /* ============ Payslip line items (detailed breakdown) ============ */
        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->string('label');
            $table->enum('type', ['earning', 'deduction', 'reimbursement', 'tax']);
            $table->decimal('amount', 12, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_components');
    }
};