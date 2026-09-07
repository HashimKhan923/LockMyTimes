<?php

namespace App\Console\Commands;

use App\Models\Main\Tenant;
use App\Models\Tenant\Notification;
use App\Services\TenantManager;
use Illuminate\Console\Command;

/**
 * Deletes in-app notifications older than 30 days from every provisioned tenant
 * database. Runs daily (see routes/console.php) — a bell feed has no real use for
 * month-old rows, and this keeps the notifications table from growing unbounded.
 */
class PruneOldNotifications extends Command
{
    protected $signature = 'notifications:prune {--slug= : Run only for a specific tenant slug} {--days=30 : Delete notifications older than this many days}';

    protected $description = 'Delete notifications older than N days (default 30) from all tenant databases';

    public function handle(TenantManager $manager): int
    {
        $days = (int) $this->option('days');
        $slug = $this->option('slug');

        $tenants = $slug
            ? Tenant::where('slug', $slug)->where('database_provisioned', true)->get()
            : Tenant::where('database_provisioned', true)->get();

        foreach ($tenants as $tenant) {
            try {
                $manager->connect($tenant);
                $deleted = Notification::where('created_at', '<', now()->subDays($days))->delete();
                $this->info("{$tenant->slug}: deleted {$deleted} notification(s) older than {$days} days.");
            } catch (\Throwable $e) {
                $this->error("Failed for {$tenant->slug}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
