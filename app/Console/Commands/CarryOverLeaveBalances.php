<?php

namespace App\Console\Commands;

use App\Models\Main\Tenant;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveType;
use App\Services\TenantManager;
use Illuminate\Console\Command;

/**
 * Year-end job: for each employee's outgoing-year balance, carries min(unused, max_carryover_days)
 * into a new balance row for the next year, and lets the remainder lapse (use-it-or-lose-it) —
 * it simply isn't carried, the old year's row stays untouched as the historical record. A leave
 * type with no max_carryover_days set carries nothing forward.
 */
class CarryOverLeaveBalances extends Command
{
    protected $signature = 'leave:carry-over {--slug= : Run only for a specific tenant slug} {--year= : Outgoing year to carry over from (defaults to the current year)}';

    protected $description = 'Carry over unused leave balances into the next year, capped at each leave type\'s max_carryover_days';

    public function handle(TenantManager $manager): int
    {
        $slug = $this->option('slug');
        $tenants = $slug
            ? Tenant::where('slug', $slug)->where('database_provisioned', true)->get()
            : Tenant::where('database_provisioned', true)->get();

        $fromYear = (int) ($this->option('year') ?: now()->year);

        foreach ($tenants as $tenant) {
            try {
                $manager->connect($tenant);
                $count = $this->carryOverForTenant($fromYear);
                $this->info("{$tenant->slug}: carried over {$count} balance(s) from {$fromYear} to ".($fromYear + 1).'.');
            } catch (\Throwable $e) {
                $this->error("Failed for {$tenant->slug}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function carryOverForTenant(int $fromYear): int
    {
        $toYear = $fromYear + 1;
        $leaveTypeCaps = LeaveType::where('is_active', true)
            ->whereNotNull('max_carryover_days')
            ->where('max_carryover_days', '>', 0)
            ->pluck('max_carryover_days', 'id');

        if ($leaveTypeCaps->isEmpty()) {
            return 0;
        }

        $balances = LeaveBalance::whereIn('leave_type_id', $leaveTypeCaps->keys())
            ->where('year', $fromYear)
            ->get();

        $count = 0;

        foreach ($balances as $balance) {
            $unused = max(0, $balance->available);
            $carry = min($unused, (float) $leaveTypeCaps[$balance->leave_type_id]);

            if ($carry <= 0) {
                continue;
            }

            $next = LeaveBalance::firstOrCreate(
                ['employee_id' => $balance->employee_id, 'leave_type_id' => $balance->leave_type_id, 'year' => $toYear],
                ['allocated' => 0, 'accrued' => 0, 'used' => 0, 'pending' => 0, 'carried_over' => 0, 'adjusted' => 0]
            );

            $next->increment('carried_over', $carry);
            $count++;
        }

        return $count;
    }
}
