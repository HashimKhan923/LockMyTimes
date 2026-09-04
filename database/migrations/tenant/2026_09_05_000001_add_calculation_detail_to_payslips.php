<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Surfaces the exact numbers behind base_pay / overtime_pay / the absence
            // deduction, so the payslip can show its own math instead of a bare total
            // the employee has to take on faith (or ask HR to explain).
            $table->unsignedInteger('working_days')->default(0)->after('leave_hours');
            $table->unsignedInteger('days_present')->default(0)->after('working_days');
            $table->unsignedInteger('days_absent')->default(0)->after('days_present');
            $table->unsignedInteger('days_late')->default(0)->after('days_absent');
            $table->decimal('daily_rate', 12, 4)->default(0)->after('days_late');
            $table->decimal('hourly_rate', 12, 4)->default(0)->after('daily_rate');
        });

        Schema::table('payslip_items', function (Blueprint $table) {
            // A short "how this was calculated" line shown as subtext under the label,
            // e.g. "6.5 hrs x $28.85/hr x 1.5" for Overtime Pay.
            $table->string('note', 255)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['working_days', 'days_present', 'days_absent', 'days_late', 'daily_rate', 'hourly_rate']);
        });
        Schema::table('payslip_items', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
