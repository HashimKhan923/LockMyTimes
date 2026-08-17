<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL enums need a raw column redefinition to add a new value.
        DB::connection('tenant')->statement(
            "ALTER TABLE payroll_runs MODIFY status ENUM('draft','calculated','approved','paid','cancelled','rejected') DEFAULT 'draft'"
        );

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });

        DB::connection('tenant')->statement(
            "ALTER TABLE payroll_runs MODIFY status ENUM('draft','calculated','approved','paid','cancelled') DEFAULT 'draft'"
        );
    }
};
