<?php

namespace App\Console\Commands;

use App\Models\Main\Tenant;
use App\Services\SuperAdminNotificationService;
use Illuminate\Console\Command;

/**
 * Notifies super admins when a trial tenant is about to expire. Runs daily (see
 * routes/console.php) and only fires at two fixed lead times — exactly 3 days out and exactly
 * 1 day out — so each tenant triggers at most two notifications total rather than one every day
 * the trial happens to be "within 3 days" of ending.
 */
class CheckExpiringTrials extends Command
{
    protected $signature = 'tenants:check-expiring-trials';

    protected $description = 'Notify super admins about trial tenants expiring in 3 days or 1 day';

    private const LEAD_DAYS = [3, 1];

    public function handle(): int
    {
        $notified = 0;

        foreach (self::LEAD_DAYS as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $tenants = Tenant::where('status', 'trial')
                ->whereDate('trial_ends_at', $targetDate)
                ->get(['id', 'company_name', 'slug']);

            foreach ($tenants as $tenant) {
                SuperAdminNotificationService::trialExpiringSoon(
                    $tenant->company_name,
                    $days,
                    route('superadmin.organizations.show', $tenant)
                );
                $notified++;
            }
        }

        $this->info("Notified super admins about {$notified} expiring trial(s).");

        return self::SUCCESS;
    }
}
