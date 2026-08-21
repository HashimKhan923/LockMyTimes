<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * users.timezone (and organizations.timezone) were created with a hardcoded
     * DEFAULT 'America/New_York' at the column level. Since EnsureEmployeeAuth /
     * EnsureEmployeeApiAuth resolve the active timezone as `$user->timezone ?? config('app.timezone')`,
     * and $user->timezone was never actually NULL (every user got the DB default), every employee
     * portal/mobile request silently used US Eastern time regardless of what the tenant's own
     * General Settings > Timezone was set to — the per-user column was meant to be an *optional*
     * personal override (see Employee::attendanceTimezone()'s use of $employee->user?->timezone for
     * remote/hybrid staff), not a silent default that outranks the tenant setting for everyone.
     *
     * Fix: make the column nullable with no default, and null out existing rows that are still
     * sitting on the untouched 'America/New_York' default so they immediately start inheriting the
     * tenant's configured timezone instead. This can't distinguish "defaulted" from "a real NYC user
     * who explicitly chose America/New_York," but the former is virtually certainly the overwhelming
     * majority given no UI ever offered this as a deliberate choice before now.
     */
    public function up(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE users MODIFY timezone VARCHAR(255) NULL DEFAULT NULL"
        );
        DB::connection('tenant')->table('users')
            ->where('timezone', 'America/New_York')
            ->update(['timezone' => null]);

        if (DB::connection('tenant')->getSchemaBuilder()->hasColumn('organizations', 'timezone')) {
            DB::connection('tenant')->statement(
                "ALTER TABLE organizations MODIFY timezone VARCHAR(255) NULL DEFAULT NULL"
            );
            DB::connection('tenant')->table('organizations')
                ->where('timezone', 'America/New_York')
                ->update(['timezone' => null]);
        }
    }

    public function down(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE users MODIFY timezone VARCHAR(255) NOT NULL DEFAULT 'America/New_York'"
        );
        if (DB::connection('tenant')->getSchemaBuilder()->hasColumn('organizations', 'timezone')) {
            DB::connection('tenant')->statement(
                "ALTER TABLE organizations MODIFY timezone VARCHAR(255) NOT NULL DEFAULT 'America/New_York'"
            );
        }
    }
};
