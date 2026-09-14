<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The employee's "request a device change" form only asks for a reason — no need to
        // capture which new device they'll use, since their very next login (after approval)
        // already sends its own device_id and binds automatically, same as a first-ever login.
        if (Schema::hasColumn('device_change_requests', 'requested_device_id')) {
            DB::statement("ALTER TABLE `device_change_requests` MODIFY `requested_device_id` VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('device_change_requests', 'requested_device_id')) {
            DB::statement("ALTER TABLE `device_change_requests` MODIFY `requested_device_id` VARCHAR(255) NOT NULL");
        }
    }
};
