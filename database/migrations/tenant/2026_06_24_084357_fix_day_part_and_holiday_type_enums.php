<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix leave_requests.day_part: normalise any 'full' values to 'full_day', then tighten enum
        if (Schema::hasTable('leave_requests')) {
            DB::statement("ALTER TABLE leave_requests MODIFY day_part ENUM('full_day','full','first_half','second_half') DEFAULT 'full_day'");
            DB::statement("UPDATE leave_requests SET day_part = 'full_day' WHERE day_part = 'full'");
            DB::statement("ALTER TABLE leave_requests MODIFY day_part ENUM('full_day','first_half','second_half') DEFAULT 'full_day'");
        }

        // Fix holidays.type: add 'optional' which was missing from the original enum
        if (Schema::hasTable('holidays')) {
            DB::statement("ALTER TABLE holidays MODIFY type ENUM('federal','state','company','religious','optional') DEFAULT 'company'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('holidays')) {
            DB::statement("ALTER TABLE holidays MODIFY type ENUM('federal','state','company','religious') DEFAULT 'company'");
        }
    }
};
