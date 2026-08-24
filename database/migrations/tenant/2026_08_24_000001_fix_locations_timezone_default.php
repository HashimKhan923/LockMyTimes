<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Same bug pattern already fixed for users/organizations.timezone
     * (see 2026_08_21_000001_fix_users_timezone_default.php): locations.timezone
     * was created with a hardcoded DEFAULT 'America/New_York', and the Admin
     * "Add Location" form's timezone <select> only ever offered six US zones
     * with no blank option — so every location silently got America/New_York
     * whether or not the admin meant to. Employee::attendanceTimezone() prefers
     * $location->timezone over the tenant's General Settings timezone for
     * onsite employees, so this silently overrode the admin's configured
     * timezone for every onsite employee at every location.
     *
     * Fix: make the column nullable with no default (falls through to
     * config('app.timezone') — the tenant's General Settings timezone — when
     * unset), and null out existing rows still sitting on the untouched
     * default, same caveat as the earlier users/organizations fix.
     */
    public function up(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE locations MODIFY timezone VARCHAR(255) NULL DEFAULT NULL"
        );
        DB::connection('tenant')->table('locations')
            ->where('timezone', 'America/New_York')
            ->update(['timezone' => null]);
    }

    public function down(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE locations MODIFY timezone VARCHAR(255) NOT NULL DEFAULT 'America/New_York'"
        );
    }
};
