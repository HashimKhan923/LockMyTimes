<?php

namespace App\Console\Commands;

use App\Models\Main\Tenant;
use Illuminate\Console\Command;

class TenantListCommand extends Command
{
    protected $signature = 'tenant:list';
    protected $description = 'List all tenants.';

    public function handle(): int
    {
        $tenants = Tenant::orderBy('id', 'desc')->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants yet. Run `php artisan tenant:provision <slug>` to create one.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Company', 'Slug', 'Status', 'DB', 'Provisioned', 'Trial Ends'],
            $tenants->map(fn ($t) => [
                $t->id,
                $t->company_name,
                $t->slug,
                $t->status,
                $t->database_name,
                $t->database_provisioned ? '' : '',
                $t->trial_ends_at?->format('M j, Y') ?? '—',
            ])->toArray()
        );

        return self::SUCCESS;
    }
}