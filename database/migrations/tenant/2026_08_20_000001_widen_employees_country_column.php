<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * employees.country was created as a 2-char ISO code column, but the employee-facing
     * profile form (resources/views/employee/profile/index.blade.php) has always been a free-text
     * "Country" field inviting full names (e.g. "Pakistan", "Canada") with maxlength=100 to match —
     * the column never matched what the form/validation actually allowed, causing a truncation
     * error on save. Widening the column to match the form's own intent, rather than narrowing the
     * form to force ISO codes (a bigger, unrequested UX change with no other code depending on this
     * column being a strict 2-char code).
     */
    public function up(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE employees MODIFY country VARCHAR(100) NOT NULL DEFAULT 'US'"
        );
    }

    public function down(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE employees MODIFY country VARCHAR(2) NOT NULL DEFAULT 'US'"
        );
    }
};
