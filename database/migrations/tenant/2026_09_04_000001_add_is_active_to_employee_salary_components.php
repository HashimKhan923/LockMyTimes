<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PayrollService::sumComponents() already filters assigned components by
     * `$ec->is_active`, but employee_salary_components was created with no
     * such column — so that filter is always null/falsy and every assigned
     * component has silently contributed $0 to every payslip since the
     * feature was scaffolded, regardless of what's actually assigned.
     */
    public function up(): void
    {
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('effective_to');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_components', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
