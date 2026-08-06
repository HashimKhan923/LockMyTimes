<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Payroll provider connections (e.g. AccountantsWorld Payroll Relief) ============ */
        Schema::create('payroll_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('payroll_relief');
            $table->text('api_key')->nullable();
            $table->string('base_url')->nullable();
            $table->enum('status', ['disconnected', 'connected', 'error'])->default('disconnected');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('employees_synced')->default(0);
            $table->integer('payslips_synced')->default(0);
            $table->timestamps();
        });

        // Lets imported records be matched back to their source on re-sync without duplicating them.
        Schema::table('employees', function (Blueprint $table) {
            $table->string('external_source')->nullable()->after('employee_code');
            $table->string('external_id')->nullable()->after('external_source');
            $table->index(['external_source', 'external_id']);
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->string('external_source')->nullable()->after('run_number');
            $table->string('external_id')->nullable()->after('external_source');
            $table->unique(['external_source', 'external_id']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->string('external_source')->nullable()->after('payslip_number');
            $table->string('external_id')->nullable()->after('external_source');
            $table->unique(['external_source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropUnique(['external_source', 'external_id']);
            $table->dropColumn(['external_source', 'external_id']);
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropUnique(['external_source', 'external_id']);
            $table->dropColumn(['external_source', 'external_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['external_source', 'external_id']);
            $table->dropColumn(['external_source', 'external_id']);
        });

        Schema::dropIfExists('payroll_integrations');
    }
};
