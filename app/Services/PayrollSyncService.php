<?php

namespace App\Services;

use App\Models\Tenant\Employee;
use App\Models\Tenant\PayrollIntegration;
use App\Models\Tenant\PayrollRun;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\PayslipItem;
use App\Services\PayrollProviders\PayrollReliefProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orchestrates pulling data from a connected payroll provider into LockMyTimes's own payroll
 * tables. Talks only to the PayrollProviderInterface contract, never to a provider's raw API.
 */
class PayrollSyncService
{
    public function testConnection(PayrollIntegration $integration): void
    {
        $this->providerFor($integration)->testConnection();
    }

    /** @return array{employees: int, payslips: int} */
    public function sync(PayrollIntegration $integration): array
    {
        $provider = $this->providerFor($integration);

        $employeesSynced = $this->syncEmployees($provider, $integration);
        $payslipsSynced = $this->syncPayslips($provider, $integration);

        $integration->update([
            'status' => 'connected',
            'last_synced_at' => now(),
            'last_error' => null,
            'employees_synced' => $integration->employees_synced + $employeesSynced,
            'payslips_synced' => $integration->payslips_synced + $payslipsSynced,
        ]);

        return ['employees' => $employeesSynced, 'payslips' => $payslipsSynced];
    }

    private function providerFor(PayrollIntegration $integration): PayrollReliefProvider
    {
        // Only one provider exists today; if a second is added later, branch on $integration->provider here.
        return new PayrollReliefProvider($integration);
    }

    private function syncEmployees(PayrollReliefProvider $provider, PayrollIntegration $integration): int
    {
        $count = 0;

        foreach ($provider->fetchEmployees() as $row) {
            $employee = Employee::where('external_source', $integration->provider)
                ->where('external_id', $row['external_id'])
                ->first();

            $attrs = array_filter([
                'first_name' => $row['first_name'] ?: null,
                'last_name' => $row['last_name'] ?: null,
                'email' => $row['email'] ?: null,
                'hire_date' => $row['hire_date'] ?: null,
            ]);

            if ($employee) {
                $employee->update($attrs);
            } else {
                Employee::create($attrs + [
                    'external_source' => $integration->provider,
                    'external_id' => $row['external_id'],
                    'employee_code' => $row['employee_code'] ?: 'PR-'.$row['external_id'],
                    'first_name' => $row['first_name'] ?: 'Unknown',
                    'last_name' => $row['last_name'] ?: '',
                    'email' => $row['email'] ?: Str::slug(($row['first_name'] ?: 'employee').'-'.$row['external_id']).'@imported.local',
                    'hire_date' => $row['hire_date'] ?: now()->toDateString(),
                ]);
            }

            $count++;
        }

        return $count;
    }

    private function syncPayslips(PayrollReliefProvider $provider, PayrollIntegration $integration): int
    {
        // Re-check a small trailing window even on repeat syncs in case a payslip was finalized late.
        $since = $integration->last_synced_at ? $integration->last_synced_at->copy()->subDays(3) : null;
        $count = 0;

        foreach ($provider->fetchPayslips($since) as $row) {
            $alreadyImported = Payslip::where('external_source', $integration->provider)
                ->where('external_id', $row['external_id'])
                ->exists();
            if ($alreadyImported) {
                continue;
            }

            $employee = Employee::where('external_source', $integration->provider)
                ->where('external_id', $row['employee_external_id'])
                ->first();

            if (! $employee || ! $row['period_start'] || ! $row['period_end'] || ! $row['pay_date']) {
                continue;
            }

            DB::connection('tenant')->transaction(function () use ($row, $employee, $integration, &$count) {
                $run = PayrollRun::firstOrCreate(
                    [
                        'external_source' => $integration->provider,
                        'external_id' => $row['period_start'].'_'.$row['period_end'],
                    ],
                    [
                        'run_number' => 'PR-EXT-'.now()->format('YmdHis').'-'.Str::random(4),
                        'period_start' => $row['period_start'],
                        'period_end' => $row['period_end'],
                        'pay_date' => $row['pay_date'],
                        'status' => 'paid',
                    ]
                );

                $totalDeductions = $row['total_deductions'] > 0
                    ? $row['total_deductions']
                    : max(0, $row['gross_pay'] - $row['net_pay']);

                $payslip = Payslip::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'payslip_number' => 'PR-'.$row['external_id'],
                    'external_source' => $integration->provider,
                    'external_id' => $row['external_id'],
                    'period_start' => $row['period_start'],
                    'period_end' => $row['period_end'],
                    'pay_date' => $row['pay_date'],
                    'regular_hours' => $row['regular_hours'],
                    'overtime_hours' => $row['overtime_hours'],
                    'gross_pay' => $row['gross_pay'],
                    'federal_tax' => $row['federal_tax'],
                    'state_tax' => $row['state_tax'],
                    'fica_ss' => $row['fica_ss'],
                    'fica_medicare' => $row['fica_medicare'],
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $row['net_pay'],
                    'status' => 'paid',
                    'payment_method' => 'direct_deposit',
                ]);

                foreach ($row['items'] as $i => $item) {
                    PayslipItem::create([
                        'payslip_id' => $payslip->id,
                        'label' => $item['label'],
                        'type' => $item['type'],
                        'amount' => $item['amount'],
                        'sort_order' => $i,
                    ]);
                }

                $count++;
            });
        }

        return $count;
    }
}
