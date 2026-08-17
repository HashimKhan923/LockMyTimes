<?php

namespace App\Services;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\AuditLog;
use App\Models\Tenant\Employee;
use App\Models\Tenant\PayrollRun;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\PayslipItem;
use App\Models\Tenant\Setting;
use App\Models\Tenant\TaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function generateRun(PayrollRun $run): void
    {
        $employees = Employee::where('employment_status', 'active')
            ->with([
                'salaryComponents.component',
                'loans'          => fn($q) => $q->where('status', 'active'),
                'salaryAdvances' => fn($q) => $q->where('status', 'active'),
            ])
            ->get();

        DB::transaction(function () use ($run, $employees) {
            foreach ($employees as $employee) {
                $this->generatePayslip($run, $employee);
            }

            $run->update([
                'total_employees' => $run->payslips()->count(),
                'total_gross'     => $run->payslips()->sum('gross_pay'),
                'total_deductions'=> $run->payslips()->sum('total_deductions'),
                'total_net'       => $run->payslips()->sum('net_pay'),
                'total_taxes'     => $run->payslips()->sum(DB::raw('federal_tax + state_tax + fica_ss + fica_medicare')),
            ]);
        });

        AuditLog::record('payroll.generated', $run, [], [
            'run_number' => $run->run_number,
            'total_employees' => $run->fresh()->total_employees,
        ]);
    }

    public function generatePayslip(PayrollRun $run, Employee $employee): Payslip
    {
        $periodStart = Carbon::parse($run->period_start);
        $periodEnd   = Carbon::parse($run->period_end);

        // Base pay
        $basePay = $this->getBasePay($employee, $run->pay_schedule ?? 'monthly');

        // Attendance
        $att         = $this->getAttendance($employee, $periodStart, $periodEnd);
        $workingDays = $this->countWorkingDays($periodStart, $periodEnd);

        // Absent deduction
        $dailyRate      = $workingDays > 0 ? round($basePay / $workingDays, 4) : 0;
        $absentDeduct   = round($dailyRate * $att['absent_days'], 2);

        // Earnings
        $bonus          = $this->sumComponents($employee, 'earning');
        $otPay          = $att['overtime_pay'];

        // Gross
        $grossPay = round($basePay - $absentDeduct + $bonus + $otPay, 2);

        // Taxes — rates from settings, fallback to statutory defaults
        $ficaSsRate      = (float) Setting::get('payroll.fica_ss_rate',       6.2)  / 100;
        $ficaMedRate     = (float) Setting::get('payroll.fica_medicare_rate',  1.45) / 100;
        $annualGross     = $grossPay * 12;
        $year            = Carbon::parse($run->pay_date)->year;
        $federalTax      = round($this->federalTax($annualGross, $year) / 12, 2);
        $stateTax        = round($this->stateTax($annualGross, $employee->state, $year) / 12, 2);
        $ficaSS          = round($grossPay * $ficaSsRate, 2);
        $ficaMedicare    = round($grossPay * $ficaMedRate, 2);

        // Other deductions (health, 401k, loans, etc.)
        $otherDeduct   = $this->sumComponents($employee, 'deduction');
        $loanDeduct    = $this->loanDeductions($employee);

        $totalDeductions = round(
            $federalTax + $stateTax + $ficaSS + $ficaMedicare
            + $otherDeduct + $loanDeduct + $absentDeduct,
            2
        );

        $netPay = round($grossPay - $totalDeductions, 2);

        $payslip = Payslip::updateOrCreate(
            [
                'payroll_run_id' => $run->id,
                'employee_id'    => $employee->id,
            ],
            [
                'payslip_number'  => 'PS-' . Carbon::parse($run->period_start)->format('Ym')
                                   . '-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                'period_start'    => $periodStart->toDateString(),
                'period_end'      => $periodEnd->toDateString(),
                'pay_date'        => $run->pay_date,
                'regular_hours'   => $workingDays * 8,
                'overtime_hours'  => $att['overtime_hours'],
                'holiday_hours'   => 0,
                'leave_hours'     => 0,
                'base_pay'        => $basePay,
                'overtime_pay'    => $otPay,
                'bonus'           => $bonus,
                'commission'      => 0,
                'reimbursement'   => 0,
                'gross_pay'       => $grossPay,
                'federal_tax'     => $federalTax,
                'state_tax'       => $stateTax,
                'local_tax'       => 0,
                'fica_ss'         => $ficaSS,
                'fica_medicare'   => $ficaMedicare,
                'health_insurance'=> 0,
                'retirement_401k' => 0,
                'other_deductions'=> round($otherDeduct + $loanDeduct + $absentDeduct, 2),
                'total_deductions'=> $totalDeductions,
                'net_pay'         => $netPay,
                'ytd_gross'       => $grossPay,
                'ytd_net'         => $netPay,
                'ytd_taxes'       => round($federalTax + $stateTax + $ficaSS + $ficaMedicare, 2),
                'status'          => 'draft',
                'payment_method'  => 'direct_deposit',
            ]
        );

        $this->writePayslipItems($payslip, [
            'earnings' => array_filter([
                'Base Pay' => $basePay,
                'Overtime Pay' => $otPay,
                'Bonus' => $bonus,
            ]),
            'taxes' => array_filter([
                'Federal Income Tax' => $federalTax,
                'State Tax' => $stateTax,
                'Social Security' => $ficaSS,
                'Medicare' => $ficaMedicare,
            ]),
            'deductions' => array_filter([
                'Absence Deduction' => $absentDeduct,
                'Loan / Advance Repayment' => $loanDeduct,
                'Other Deductions' => $otherDeduct,
            ]),
        ]);

        return $payslip;
    }

    /**
     * Populate the itemized PayslipItem breakdown for a generated payslip, mirroring the shape
     * PayrollSyncService already writes for third-party-imported payslips (see its own loop over
     * $row['items']) so a payslip looks the same regardless of source. Clears any prior items
     * first since generatePayslip() can be called again for the same run (regenerate).
     */
    private function writePayslipItems(Payslip $payslip, array $groups): void
    {
        $payslip->items()->delete();

        $sortOrder = 0;
        foreach (['earnings' => 'earning', 'taxes' => 'tax', 'deductions' => 'deduction'] as $group => $type) {
            foreach ($groups[$group] as $label => $amount) {
                if ($amount <= 0) {
                    continue;
                }
                PayslipItem::create([
                    'payslip_id' => $payslip->id,
                    'label' => $label,
                    'type' => $type,
                    'amount' => $amount,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }

    /* ================================================================
     | PRIVATE HELPERS
     |================================================================*/

    private function getBasePay(Employee $employee, ?string $schedule): float
    {
        $annual = (float) ($employee->base_salary ?? 0);
        return match($schedule ?? 'monthly') {
            'monthly'      => round($annual / 12, 2),
            'bi_weekly'    => round($annual / 26, 2),
            'semi_monthly' => round($annual / 24, 2),
            'weekly'       => round($annual / 52, 2),
            default        => round($annual / 12, 2),
        };
    }

    private function getAttendance(Employee $employee, Carbon $start, Carbon $end): array
    {
        $records   = Attendance::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $otHours    = (float) $records->sum('overtime_hours');
        $otRate     = (float) Setting::get('payroll.overtime_rate', 1.5);
        $hourlyRate = $employee->base_salary > 0
            ? round((float)$employee->base_salary / 52 / 40, 4) : 0;

        return [
            'days_worked'    => $records->where('status', 'present')->count(),
            'absent_days'    => $records->where('status', 'absent')->count(),
            'late_days'      => $records->where('is_late', true)->count(),
            'overtime_hours' => round($otHours, 2),
            'overtime_pay'   => round($otHours * $hourlyRate * $otRate, 2),
        ];
    }

    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        $cur  = $start->copy();
        while ($cur->lte($end)) {
            if (! $cur->isWeekend()) $days++;
            $cur->addDay();
        }
        return $days;
    }

    private function sumComponents(Employee $employee, string $type): float
    {
        return (float) $employee->salaryComponents
            ->filter(fn($ec) => ($ec->component->type ?? '') === $type && $ec->is_active)
            ->sum('amount');
    }

    private function loanDeductions(Employee $employee): float
    {
        $loans = $employee->loans
            ->where('status', 'active')
            ->sum('monthly_installment');

        $advances = $employee->salaryAdvances
            ->where('status', 'active')
            ->sum('deduction_per_month');

        return round((float)$loans + (float)$advances, 2);
    }

    /**
     * Federal income tax on an annual gross figure. Reads brackets + standard-deduction
     * (wage_base) from a `tax_settings` row for the given year when one exists — this is what
     * lets an admin actually change the numbers from Settings > Tax Settings and have it affect
     * real payroll. Falls back to the last-known (2024) statutory brackets so payroll never
     * breaks for a tenant/year that hasn't been configured yet.
     */
    private function federalTax(float $annualGross, int $year): float
    {
        $setting = TaxSetting::where('tax_type', 'federal_income')
            ->whereNull('state')
            ->where('year', $year)
            ->where('is_active', true)
            ->first();

        if ($setting) {
            return $this->applyTaxSetting($annualGross, $setting);
        }

        return $this->applyBrackets(max(0, $annualGross - 14600), self::DEFAULT_FEDERAL_BRACKETS_2024);
    }

    /**
     * State income tax on an annual gross figure, keyed by the employee's own address state.
     * No tax_settings row for that state/year simply means $0 — most US states have no income
     * tax, and this stays correct-by-default until an admin configures one that does.
     */
    private function stateTax(float $annualGross, ?string $state, int $year): float
    {
        if (! $state) {
            return 0;
        }

        $setting = TaxSetting::where('tax_type', 'state_income')
            ->where('state', $state)
            ->where('year', $year)
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            return 0;
        }

        return $this->applyTaxSetting($annualGross, $setting);
    }

    /** A tax_settings row is either bracket-based or a flat rate — apply whichever is configured. */
    private function applyTaxSetting(float $annualGross, TaxSetting $setting): float
    {
        $taxable = max(0, $annualGross - (float) ($setting->wage_base ?? 0));

        if (! empty($setting->brackets)) {
            return $this->applyBrackets($taxable, $setting->brackets);
        }

        if ($setting->flat_rate !== null) {
            return round($taxable * ((float) $setting->flat_rate / 100), 2);
        }

        return 0;
    }

    /** @param array<int, array{min: float, max: float, base: float, rate: float}> $brackets */
    private function applyBrackets(float $taxable, array $brackets): float
    {
        foreach ($brackets as $bracket) {
            if ($taxable <= $bracket['max']) {
                return round($bracket['base'] + ($taxable - $bracket['min']) * $bracket['rate'], 2);
            }
        }

        return 0;
    }

    private const DEFAULT_FEDERAL_BRACKETS_2024 = [
        ['min' => 0,      'max' => 11600,      'base' => 0,      'rate' => 0.10],
        ['min' => 11600,  'max' => 47150,      'base' => 1160,   'rate' => 0.12],
        ['min' => 47150,  'max' => 100525,     'base' => 5426,   'rate' => 0.22],
        ['min' => 100525, 'max' => 191950,     'base' => 17168,  'rate' => 0.24],
        ['min' => 191950, 'max' => 243725,     'base' => 39110,  'rate' => 0.32],
        ['min' => 243725, 'max' => 609350,     'base' => 55678,  'rate' => 0.35],
        ['min' => 609350, 'max' => PHP_INT_MAX, 'base' => 183647, 'rate' => 0.37],
    ];
}