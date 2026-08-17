<?php

namespace App\Console\Commands;

use App\Models\Main\Tenant;
use App\Models\Tenant\Employee;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\Setting;
use App\Services\TenantManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Applies each active LeaveType's accrual_method/accrual_rate to every eligible employee's
 * current-year LeaveBalance. Intended to run monthly (see routes/console.php) — that cadence
 * is what "monthly" accrual applies on every run; "quarterly"/"annually" only add on the
 * calendar months that make sense for those cycles, and "per_pay_period" approximates using the
 * tenant's configured pay schedule (payroll.pay_schedule) since the command itself only runs once
 * a month. This is deliberately a reasonable approximation, not a payroll-grade period tracker.
 */
class AccrueLeaveBalances extends Command
{
    protected $signature = 'leave:accrue {--slug= : Run only for a specific tenant slug}';

    protected $description = 'Accrue leave balances for all eligible employees based on each leave type\'s accrual policy';

    public function handle(TenantManager $manager): int
    {
        $slug = $this->option('slug');
        $tenants = $slug
            ? Tenant::where('slug', $slug)->where('database_provisioned', true)->get()
            : Tenant::where('database_provisioned', true)->get();

        foreach ($tenants as $tenant) {
            try {
                $manager->connect($tenant);
                $count = $this->accrueForTenant();
                $this->info("{$tenant->slug}: accrued balances for {$count} employee/leave-type pair(s).");
            } catch (\Throwable $e) {
                $this->error("Failed for {$tenant->slug}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function accrueForTenant(): int
    {
        $year = now()->year;
        $leaveTypes = LeaveType::where('is_active', true)->where('accrual_method', '!=', 'none')->get();
        if ($leaveTypes->isEmpty()) {
            return 0;
        }

        $employees = Employee::where('employment_status', 'active')->get();
        $count = 0;

        foreach ($leaveTypes as $type) {
            $amount = $this->accrualAmountThisRun($type);
            if ($amount <= 0) {
                continue;
            }

            foreach ($employees as $employee) {
                if (! $this->isEligible($employee, $type)) {
                    continue;
                }

                $balance = LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                    ['allocated' => $type->default_days_per_year, 'accrued' => 0, 'used' => 0, 'pending' => 0, 'carried_over' => 0, 'adjusted' => 0]
                );

                $balance->increment('accrued', $amount);
                $count++;
            }
        }

        return $count;
    }

    private function isEligible(Employee $employee, LeaveType $type): bool
    {
        if (! $type->min_service_months) {
            return true;
        }
        if (! $employee->hire_date) {
            return false;
        }

        return Carbon::parse($employee->hire_date)->diffInMonths(now()) >= $type->min_service_months;
    }

    /** How much of $type->accrual_rate to add on THIS run, given its accrual_method and today's date. */
    private function accrualAmountThisRun(LeaveType $type): float
    {
        $rate = (float) $type->accrual_rate;

        return match ($type->accrual_method) {
            'monthly' => $rate,
            'quarterly' => in_array(now()->month, [1, 4, 7, 10], true) ? $rate : 0,
            'annually' => now()->month === 1 ? $rate : 0,
            'per_pay_period' => $rate * $this->payPeriodsPerMonth(),
            default => 0,
        };
    }

    private function payPeriodsPerMonth(): float
    {
        return match (Setting::get('payroll.pay_schedule', 'monthly')) {
            'weekly' => 52 / 12,
            'bi_weekly' => 26 / 12,
            'semi_monthly' => 2,
            default => 1,
        };
    }
}
