<?php

namespace App\Services;

use App\Models\Main\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class TenantManager
{
    /**
     * The currently active tenant for this request (if any).
     */
    protected static ?Tenant $current = null;

    /* ====================================================================
     | Current Tenant Helpers
     |====================================================================*/

    public static function current(): ?Tenant
    {
        return static::$current;
    }

    public static function setCurrent(?Tenant $tenant): void
    {
        static::$current = $tenant;
    }

    public static function hasCurrent(): bool
    {
        return static::$current !== null;
    }

    /* ====================================================================
     | Connection Switching
     |====================================================================*/

    /**
     * Switch the "tenant" connection to point at the given tenant's database.
     * After this call, any Eloquent model on the "tenant" connection will hit
     * the right database.
     */
public function connect(Tenant $tenant): void
{
    static::setCurrent($tenant);

    Config::set('database.connections.tenant.database', $tenant->database_name);

    DB::purge('tenant');
    DB::reconnect('tenant');
    DB::setDefaultConnection('tenant');

    // Flush Spatie's in-memory permission cache so the new tenant's
    // roles/permissions are loaded fresh from the correct database.
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

    /**
     * Reset to the main connection (used after a tenant request completes).
     */
    public function disconnect(): void
    {
        static::setCurrent(null);
        Config::set('database.connections.tenant.database', null);
        DB::setDefaultConnection('main');
        DB::purge('tenant');
    }

    /* ====================================================================
     | Tenant Provisioning (creates a brand new tenant DB + schema + seeds)
     |====================================================================*/

    /**
     * Provision a tenant: create its database, run all tenant migrations,
     * seed defaults, and mark it as provisioned.
     */
    public function provision(Tenant $tenant): void
    {
        $this->createDatabase($tenant->database_name);

        $this->connect($tenant);

        try {
            $this->runMigrations();
            $this->seedDefaults($tenant);

            $tenant->update(['database_provisioned' => true, 'status' => 'trial']);
        } catch (Throwable $e) {
            $this->disconnect();
            throw $e;
        }

        $this->disconnect();
    }

    /**
     * Drop a tenant's database (use with caution — only for cancellations).
     */
    public function deprovision(Tenant $tenant): void
    {
        $this->dropDatabase($tenant->database_name);
        $tenant->update(['database_provisioned' => false]);
    }

    /* ====================================================================
     | Internal helpers
     |====================================================================*/

    protected function createDatabase(string $name): void
    {
        $charset   = 'utf8mb4';
        $collation = 'utf8mb4_unicode_ci';

        // Use the main connection's PDO to create the database
        DB::connection('main')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET {$charset} COLLATE {$collation};"
        );
    }

    protected function dropDatabase(string $name): void
    {
        DB::connection('main')->statement("DROP DATABASE IF EXISTS `{$name}`;");
    }

    /**
     * Run all migration files in database/migrations/tenant against the
     * currently-active tenant connection.
     */
    protected function runMigrations(): void
    {
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);
    }

    /**
     * Seed default data into the freshly-provisioned tenant DB.
     */
    protected function seedDefaults(Tenant $tenant): void
    {
        Artisan::call('db:seed', [
            '--class'    => \Database\Seeders\Tenant\TenantDefaultSeeder::class,
            '--database' => 'tenant',
            '--force'    => true,
        ]);
    }

    /* ====================================================================
     | Run callable within tenant context
     |====================================================================*/

    /**
     * Execute a callback inside a tenant's context.
     * Useful for jobs, console commands, etc.
     */
    public function runFor(Tenant $tenant, callable $callback): mixed
    {
        $previous = static::current();

        $this->connect($tenant);

        try {
            return $callback($tenant);
        } finally {
            if ($previous) {
                $this->connect($previous);
            } else {
                $this->disconnect();
            }
        }
    }
}