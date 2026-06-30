<?php

namespace App\Services;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Employee;
use App\Models\Tenant\PayrollRun;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\Setting;
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
        $federalTax      = round($this->federalTax($annualGross) / 12, 2);
        $ficaSS          = round($grossPay * $ficaSsRate, 2);
        $ficaMedicare    = round($grossPay * $ficaMedRate, 2);

        // Other deductions (health, 401k, loans, etc.)
        $otherDeduct   = $this->sumComponents($employee, 'deduction');
        $loanDeduct    = $this->loanDeductions($employee);

        $totalDeductions = round(
            $federalTax + $ficaSS + $ficaMedicare
            + $otherDeduct + $loanDeduct + $absentDeduct,
            2
        );

        $netPay = round($grossPay - $totalDeductions, 2);

        return Payslip::updateOrCreate(
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
                'state_tax'       => 0,
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
                'ytd_taxes'       => round($federalTax + $ficaSS + $ficaMedicare, 2),
                'status'          => 'draft',
                'payment_method'  => 'direct_deposit',
            ]
        );
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

    private function federalTax(float $annualGross): float
    {
        $taxable  = max(0, $annualGross - 14600);
        $brackets = [
            [0,      11600,  0,      0.10],
            [11600,  47150,  1160,   0.12],
            [47150,  100525, 5426,   0.22],
            [100525, 191950, 17168,  0.24],
            [191950, 243725, 39110,  0.32],
            [243725, 609350, 55678,  0.35],
            [609350, PHP_INT_MAX, 183647, 0.37],
        ];
        foreach ($brackets as [$min, $max, $base, $rate]) {
            if ($taxable <= $max) {
                return round($base + ($taxable - $min) * $rate, 2);
            }
        }
        return 0;
    }
}