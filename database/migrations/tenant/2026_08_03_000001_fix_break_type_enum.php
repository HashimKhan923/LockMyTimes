<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The break-type picker (web) offers "Tea / Coffee" as 'tea', and both
        // AttendanceController@startBreak implementations default to 'tea'
        // when no type is supplied (web quick-break, mobile "Take a break"
        // button) — but the original enum only had 'short_break', which
        // nothing actually sends. Every 'tea' insert was silently truncated
        // by MySQL (SQLSTATE 01000 / 1265 "Data truncated for column").
        if (Schema::hasTable('attendance_breaks')) {
            DB::statement("ALTER TABLE attendance_breaks MODIFY break_type ENUM('lunch','tea','short_break','personal','other') DEFAULT 'tea'");

            // Any break started before this fix with the (always-invalid) 'tea'
            // type would have been silently stored as '' by MySQL rather than
            // rejected outright — repair those rows now that 'tea' is valid.
            DB::statement("UPDATE attendance_breaks SET break_type = 'tea' WHERE break_type = ''");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_breaks')) {
            DB::statement("ALTER TABLE attendance_breaks MODIFY break_type ENUM('lunch','short_break','personal','other') DEFAULT 'short_break'");
        }
    }
};
