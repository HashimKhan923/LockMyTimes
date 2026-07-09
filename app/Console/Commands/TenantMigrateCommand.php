<?php

namespace App\Console\Commands;

use App\Models\Main\Tenant;
use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate
                            {--slug= : Run only for a specific tenant slug}
                            {--fresh : Drop all tables and re-migrate (DANGEROUS)}
                            {--seed : Run seeders after migration}';

    protected $description = 'Run tenant database migrations for all tenants (or a specific one)';

    public function handle(TenantManager $manager): int
    {
        $slug    = $this->option('slug');
        $tenants = $slug
            ? Tenant::where('slug', $slug)->where('database_provisioned', true)->get()
            : Tenant::where('database_provisioned', true)->get();

        if ($tenants->isEmpty()) {
            $this->error('No provisioned tenants found.');
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->slug} ({$tenant->database_name})");

            try {
                $manager->connect($tenant);

                if ($this->option('fresh')) {
                    $this->warn(" Dropping all tables for {$tenant->slug}...");
                    $this->dropAllTables();
                }

                // Run migrations from the tenant migrations folder
                $this->call('migrate', [
                    '--path'     => 'database/migrations/tenant',
                    '--database' => 'tenant',
                    '--force'    => true,
                ]);

                if ($this->option('seed')) {
                    $this->call('db:seed', [
                        '--class'    => 'Database\\Seeders\\Tenant\\TenantDefaultSeeder',
                        '--database' => 'tenant',
                        '--force'    => true,
                    ]);
                }

                $this->info(" Done: {$tenant->slug}");

            } catch (\Throwable $e) {
                $this->error(" Failed for {$tenant->slug}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function dropAllTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}