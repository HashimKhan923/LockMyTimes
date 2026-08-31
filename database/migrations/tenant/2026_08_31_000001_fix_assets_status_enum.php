<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * assets.status was created with enum('available','assigned','in_repair','lost','retired'),
     * but every consumer of this column — AssetController's validation, the admin
     * index stats query, and both admin/assets blade views — consistently uses
     * 'maintenance' instead of 'in_repair'. Saving status=maintenance today would
     * be rejected by MySQL's strict enum check. Align the column with what the
     * app actually uses, and migrate any existing 'in_repair' rows forward.
     */
    public function up(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE assets MODIFY status ENUM('available','assigned','maintenance','retired','lost') DEFAULT 'available'"
        );
        DB::connection('tenant')->table('assets')
            ->where('status', 'in_repair')
            ->update(['status' => 'maintenance']);
    }

    public function down(): void
    {
        DB::connection('tenant')->table('assets')
            ->where('status', 'maintenance')
            ->update(['status' => 'in_repair']);
        DB::connection('tenant')->statement(
            "ALTER TABLE assets MODIFY status ENUM('available','assigned','in_repair','lost','retired') DEFAULT 'available'"
        );
    }
};
