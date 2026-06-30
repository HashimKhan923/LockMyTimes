<?php

namespace App\Console\Commands;

use App\Models\Main\Tenant;
use App\Services\TenantManager;
use Illuminate\Console\Command;

class TenantRemoveCommand extends Command
{
    protected $signature = 'tenant:remove {slug : The slug of the tenant to remove} {--force : Skip confirmation}';
    protected $description = 'Remove a tenant and DROP its database (destructive!).';

    public function handle(TenantManager $manager): int
    {
        $tenant = Tenant::where('slug', $this->argument('slug'))->first();
        if (! $tenant) {
            $this->error("Tenant '{$this->argument('slug')}' not found.");
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Drop database '{$tenant->database_name}' and delete tenant '{$tenant->company_name}'?")) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $manager->deprovision($tenant);
        $tenant->forceDelete();

        $this->info("✅ Tenant '{$tenant->slug}' removed.");
        return self::SUCCESS;
    }
}