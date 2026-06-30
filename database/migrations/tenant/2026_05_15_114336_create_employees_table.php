<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Personal info
            $table->string('employee_code', 30)->unique();    // "EMP-0001"
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('preferred_name')->nullable();
            $table->string('email')->unique();
            $table->string('personal_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'non_binary', 'prefer_not_to_say'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'separated'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('avatar')->nullable();

            // US specific (Tax / Compliance)
            $table->string('ssn_encrypted')->nullable();      // encrypted in app
            $table->enum('employee_type', ['w2', '1099', 'contractor', 'intern'])->default('w2');
            $table->boolean('i9_verified')->default(false);
            $table->date('i9_verified_date')->nullable();

            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 50)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('US');

            // Employment
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->enum('employment_status', ['active', 'on_leave', 'suspended', 'terminated', 'retired'])->default('active');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'temporary'])->default('full_time');
            $table->date('hire_date');
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();

            // Compensation
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->enum('salary_frequency', ['hourly', 'weekly', 'bi_weekly', 'semi_monthly', 'monthly', 'annually'])->default('annually');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_exempt')->default(false);             // FLSA exempt or non-exempt

            // Banking (for direct deposit)
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number_encrypted')->nullable();
            $table->string('bank_routing_number_encrypted')->nullable();
            $table->enum('bank_account_type', ['checking', 'savings'])->nullable();

            // Tax info
            $table->string('federal_filing_status')->nullable();      // Single, Married, HoH
            $table->integer('federal_allowances')->default(0);
            $table->decimal('additional_federal_withholding', 8, 2)->default(0);
            $table->string('state_filing_status')->nullable();
            $table->integer('state_allowances')->default(0);

            // Misc
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employment_status', 'department_id']);
        });

        // Now that employees exists, add the deferred FK on departments.manager_id
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });

        // And the deferred FK on users.employee_id
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        /* ============ Employee Emergency Contacts ============ */
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        /* ============ Employee Documents (personal docs uploaded by HR) ============ */
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type');             // "W-4", "I-9", "Contract", "ID"
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_confidential')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_emergency_contacts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::dropIfExists('employees');
    }
};