<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Loan Types ============ */
        Schema::create('loan_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // "Personal Loan", "Education Loan"
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->default('#6C7DF7');

            // Loan terms
            $table->decimal('default_interest_rate', 5, 2)->default(0);   // annual %
            $table->enum('interest_type', ['simple', 'reducing_balance', 'flat', 'zero_interest'])->default('reducing_balance');
            $table->decimal('max_amount', 14, 2)->nullable();
            $table->decimal('min_amount', 14, 2)->default(0);
            $table->integer('max_tenure_months')->default(12);
            $table->integer('min_tenure_months')->default(1);

            // Eligibility
            $table->integer('min_service_months')->default(0);            // months of employment required
            $table->decimal('min_salary', 12, 2)->default(0);             // minimum employee salary required
            $table->decimal('max_salary_multiplier', 6, 2)->default(3);   // max loan = X × monthly salary
            $table->integer('cooldown_months')->default(0);               // wait between loans of same type

            // Settings
            $table->boolean('requires_guarantor')->default(false);
            $table->boolean('requires_documentation')->default(false);
            $table->boolean('requires_collateral')->default(false);
            $table->boolean('auto_deduct_from_payroll')->default(true);
            $table->boolean('allow_early_repayment')->default(true);
            $table->decimal('early_repayment_fee_percent', 5, 2)->default(0);
            $table->decimal('late_payment_fee_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ============ Loans ============ */
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('loan_type_id')->constrained('loan_types');

            $table->string('loan_number')->unique();             // "LN-2025-000001"

            // Principal & Interest
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->enum('interest_type', ['simple', 'reducing_balance', 'flat', 'zero_interest'])->default('reducing_balance');
            $table->decimal('total_interest', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);              // principal + interest
            $table->decimal('processing_fee', 12, 2)->default(0);

            // Tenure
            $table->integer('tenure_months');
            $table->decimal('emi_amount', 12, 2);                // monthly installment
            $table->date('first_emi_date')->nullable();

            // Outstanding
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('amount_remaining', 14, 2);
            $table->integer('installments_paid')->default(0);
            $table->integer('installments_remaining');

            // Purpose & docs
            $table->text('purpose')->nullable();
            $table->string('agreement_document')->nullable();
            $table->json('supporting_documents')->nullable();

            // Guarantor
            $table->foreignId('guarantor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('guarantor_external_name')->nullable();
            $table->string('guarantor_external_phone')->nullable();
            $table->string('guarantor_document')->nullable();

            // Workflow
            $table->enum('status', [
                'pending',           // submitted, awaiting review
                'under_review',      // being processed
                'approved',          // approved but not yet disbursed
                'rejected',
                'disbursed',         // money paid to employee
                'active',            // currently being repaid
                'completed',         // fully repaid
                'defaulted',         // missed too many payments
                'settled',           // closed early with settlement
                'cancelled',
            ])->default('pending');

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
            $table->string('disbursement_method')->nullable();   // bank_transfer, cash, check
            $table->string('disbursement_reference')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('approver_comments')->nullable();
            $table->text('notes')->nullable();

            // Settings
            $table->boolean('auto_deduct_from_payroll')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
        });

        /* ============ Loan Repayment Schedule ============ */
        /* Auto-generated EMI schedule when a loan is approved */
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->integer('installment_number');               // 1, 2, 3...
            $table->date('due_date');
            $table->decimal('principal_component', 12, 2);
            $table->decimal('interest_component', 12, 2);
            $table->decimal('emi_amount', 12, 2);
            $table->decimal('balance_after', 14, 2);             // remaining after this payment

            // Payment tracking
            $table->date('paid_date')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'partially_paid', 'overdue', 'waived', 'skipped'])->default('pending');
            $table->enum('payment_source', ['payroll_deduction', 'manual', 'bank_transfer', 'cash', 'other'])->nullable();
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->index(['loan_id', 'due_date']);
            $table->unique(['loan_id', 'installment_number']);
        });

        /* ============ Salary Advances (quick & simple — separate from loans) ============ */
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('advance_number')->unique();           // "ADV-2025-000001"
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->string('supporting_document')->nullable();

            // Repayment plan
            $table->enum('repayment_type', ['one_time', 'installments'])->default('one_time');
            $table->integer('installments_count')->default(1);    // 1 = one-time deduction
            $table->decimal('per_installment_amount', 12, 2);
            $table->date('first_deduction_date')->nullable();

            // Outstanding
            $table->decimal('amount_repaid', 12, 2)->default(0);
            $table->decimal('amount_remaining', 12, 2);
            $table->integer('installments_paid')->default(0);

            // Workflow
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'disbursed',
                'active',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('approver_comments')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
        });

        /* ============ Salary Advance Deductions (tracks each payroll deduction) ============ */
        Schema::create('advance_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_advance_id')->constrained('salary_advances')->cascadeOnDelete();
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->integer('deduction_number');                  // 1, 2, 3...
            $table->date('deduction_date');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'deducted', 'skipped', 'waived'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['salary_advance_id', 'deduction_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_deductions');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_types');
    }
};