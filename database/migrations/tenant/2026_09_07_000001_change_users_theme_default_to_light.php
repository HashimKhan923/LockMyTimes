<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The column's own default was 'system', which follows the visiting browser/OS's
        // color scheme — meaning a brand-new employee could land on a dark portal on their
        // very first login with no choice of their own behind it. EmployeeController::store()
        // now sets 'light' explicitly for new users too; this changes the column default so
        // any OTHER code path that creates a users row without specifying theme also gets a
        // sane default, rather than silently inheriting 'system' again.
        if (Schema::hasColumn('users', 'theme')) {
            DB::statement("ALTER TABLE `users` MODIFY `theme` VARCHAR(10) NOT NULL DEFAULT 'light'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'theme')) {
            DB::statement("ALTER TABLE `users` MODIFY `theme` VARCHAR(10) NOT NULL DEFAULT 'system'");
        }
    }
};
