<?php

namespace App\Console\Commands;

use App\Models\Main\SubscriptionPlan;
use App\Models\Main\Tenant;
use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class TenantProvisionCommand extends Command
{
    protected $signature = 'tenant:provision
        {slug : The URL slug for this tenant (e.g. acme-corp)}
        {--company= : Company name}
        {--email= : Primary contact email}
        {--name= : Primary contact name}
        {--password=password : Initial password for the tenant admin}
        {--plan=professional : Plan slug to assign}
        {--fresh : Drop and recreate the database if it already exists}';

    protected $description = 'Provision a new tenant: create DB, run migrations, seed defaults, create admin user.';

    public function handle(TenantManager $manager): int
    {
        $slug = Str::slug($this->argument('slug'));

        $companyName  = $this->option('company')  ?: Str::title(str_replace('-', ' ', $slug));
        $contactEmail = $this->option('email')    ?: "admin@{$slug}.test";
        $contactName  = $this->option('name')     ?: 'Tenant Admin';
        $password     = $this->option('password') ?: 'password';
        $planSlug     = $this->option('plan')     ?: 'professional';

        $this->info(" Provisioning tenant '{$slug}'…");

        // 1) Find plan
        $plan = SubscriptionPlan::where('slug', $planSlug)->first();
        if (! $plan) {
            $this->error(" Plan '{$planSlug}' not found. Run `php artisan db:seed` first.");
            return self::FAILURE;
        }

        // 2) Create/find the tenant row in the MAIN db
        $databaseName = Tenant::generateDatabaseName($slug);

        if ($this->option('fresh')) {
            $this->warn("️ --fresh: dropping database '{$databaseName}' if it exists…");
            DB::connection('main')->statement("DROP DATABASE IF EXISTS `{$databaseName}`;");

            Tenant::where('slug', $slug)->forceDelete();
        }

        $tenant = Tenant::updateOrCreate(
            ['slug' => $slug],
            [
                'company_name'   => $companyName,
                'contact_name'   => $contactName,
                'contact_email'  => $contactEmail,
                'database_name'  => $databaseName,
                'status'         => 'trial',
                'trial_ends_at'  => now()->addDays((int) config('tenancy.trial_days', 14)),
                'country'        => 'US',
                'timezone'       => 'America/New_York',
            ]
        );

        // 3) Provision the DB
        try {
            $this->info("️ Creating database '{$databaseName}'…");
            $this->info("️ Running migrations…");
            $this->info(" Seeding defaults…");

            $manager->provision($tenant);

            // 4) Create initial admin user inside the tenant DB
            $this->info(" Creating tenant admin user…");
            $manager->runFor($tenant, function () use ($contactName, $contactEmail, $password) {
                $user = \App\Models\Tenant\User::firstOrCreate(
                    ['email' => $contactEmail],
                    [
                        'name'              => $contactName,
                        'password'          => Hash::make($password),
                        'is_active'         => true,
                        'email_verified_at' => now(),
                    ]
                );

                $user->assignRole('Tenant Admin');
            });

            // 5) Create a sample subscription record
            \App\Models\Main\Subscription::firstOrCreate(
                ['tenant_id' => $tenant->id, 'status' => 'trialing'],
                [
                    'plan_id'                  => $plan->id,
                    'billing_cycle'            => 'monthly',
                    'amount'                   => $plan->monthly_price,
                    'status'                   => 'trialing',
                    'trial_starts_at'          => now(),
                    'trial_ends_at'            => $tenant->trial_ends_at,
                    'current_period_starts_at' => now(),
                    'current_period_ends_at'   => $tenant->trial_ends_at,
                ]
            );

            $this->newLine();
            $this->info(" Tenant provisioned successfully!");
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['Company',        $companyName],
                    ['Slug',           $slug],
                    ['Database',       $databaseName],
                    ['Admin URL',      $tenant->adminUrl()],
                    ['Portal URL',     $tenant->employeeUrl()],
                    ['Admin Email',    $contactEmail],
                    ['Admin Password', $password],
                    ['Plan',           $plan->name],
                    ['Trial Ends',     $tenant->trial_ends_at?->format('M j, Y') ?? '—'],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(" Failed: ".$e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}