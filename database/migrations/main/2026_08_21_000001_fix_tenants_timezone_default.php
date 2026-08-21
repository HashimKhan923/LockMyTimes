<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * tenants.timezone (main DB) was created with a hardcoded DEFAULT 'America/New_York'. It's
     * only ever read as the last-resort fallback in IdentifyTenant (before config('app.timezone'),
     * which is 'UTC') for a tenant that hasn't configured General Settings > Timezone yet — so this
     * only affected brand-new tenants' pre-onboarding experience, not the main users.timezone bug
     * (see the tenant migration in the same batch), but it's the same landmine pattern and silently
     * biases every new tenant toward US Eastern time regardless of where they actually are.
     */
    public function up(): void
    {
        DB::connection('main')->statement(
            "ALTER TABLE tenants MODIFY timezone VARCHAR(255) NULL DEFAULT NULL"
        );
        DB::connection('main')->table('tenants')
            ->where('timezone', 'America/New_York')
            ->update(['timezone' => null]);
    }

    public function down(): void
    {
        DB::connection('main')->statement(
            "ALTER TABLE tenants MODIFY timezone VARCHAR(255) NOT NULL DEFAULT 'America/New_York'"
        );
    }
};
