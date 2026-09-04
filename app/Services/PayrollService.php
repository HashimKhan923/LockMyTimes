<?php

namespace App\Services;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\AuditLog;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Loan;
use App\Models\Tenant\PayrollRun;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\PayslipItem;
use App\Models\Tenant\SalaryAdvance;
use App\Models\Tenant\Setting;
use App\Models\Tenant\TaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function generateRun(PayrollRun $run): void
    {
        // Loans/salary advances are deliberately NOT eager-loaded here — resolveLoanRepayments()
        // and resolveAdvanceDeductions() below query them directly per employee, since which
        // ones are relevant depends on the specific payslip being (re)generated, not just the
        // employee's current status.
        $employees = Employee::where('employment_status', 'active')
            ->with(['salaryComponents.component'])
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

        // Assigned salary components, including 'tax' type ones (keyed by their catalog
        // code: FED_TAX / STATE_TAX / FICA_SS / FICA_MED) — see the Taxes block below,
        // which reads straight from this collection with no automatic calculation.
        $components            = $this->assignedComponents($employee);
        $earningComponents     = $components->filter(fn ($ec) => $ec->component->type === 'earning');
        $deductionComponents   = $components->filter(fn ($ec) => $ec->component->type === 'deduction');
        $reimbursementComponents = $components->filter(fn ($ec) => $ec->component->type === 'reimbursement');
        $taxComponents          = $components->filter(fn ($ec) => $ec->component->type === 'tax')
            ->keyBy(fn ($ec) => $ec->component->code);

        // Earnings
        $bonus          = (float) $earningComponents->sum('amount');
        $otPay          = $att['overtime_pay'];
        $reimbursement  = (float) $reimbursementComponents->sum('amount');

        // Gross (reimbursements are non-taxable — added at the net stage below, not here)
        $grossPay = round($basePay - $absentDeduct + $bonus + $otPay, 2);

        // Taxes — driven entirely by what's assigned in Salary Components, same as
        // earning/deduction/reimbursement. Nothing is deducted for a tax line unless
        // the admin has explicitly assigned that Tax component to this employee; there
        // is no automatic bracket/rate calculation running in the background.
        $federalTax   = (float) ($taxComponents['FED_TAX']->amount ?? 0);
        $stateTax     = (float) ($taxComponents['STATE_TAX']->amount ?? 0);
        $ficaSS       = (float) ($taxComponents['FICA_SS']->amount ?? 0);
        $ficaMedicare = (float) ($taxComponents['FICA_MED']->amount ?? 0);

        // Other deductions (health, 401k, etc. — loans/advances are resolved further down,
        // once the payslip exists, since each is tied to a specific due installment)
        $otherDeduct = (float) $deductionComponents->sum('amount');

        // Placeholder totals — recomputed below once loan/advance deductions are known.
        $totalDeductions = round($federalTax + $stateTax + $ficaSS + $ficaMedicare + $otherDeduct + $absentDeduct, 2);
        $netPay = round($grossPay - $totalDeductions + $reimbursement, 2);

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
                'reimbursement'   => $reimbursement,
                'gross_pay'       => $grossPay,
                'federal_tax'     => $federalTax,
                'state_tax'       => $stateTax,
                'local_tax'       => 0,
                'fica_ss'         => $ficaSS,
                'fica_medicare'   => $ficaMedicare,
                'health_insurance'=> 0,
                'retirement_401k' => 0,
                'other_deductions'=> round($otherDeduct + $absentDeduct, 2),
                'total_deductions'=> $totalDeductions,
                'net_pay'         => $netPay,
                'ytd_gross'       => $grossPay,
                'ytd_net'         => $netPay,
                'ytd_taxes'       => round($federalTax + $stateTax + $ficaSS + $ficaMedicare, 2),
                'status'          => 'draft',
                'payment_method'  => 'direct_deposit',
            ]
        );

        // Loans and salary advances — resolved now that the payslip has a real ID, since
        // each deduction is linked back to the specific loan_repayments/advance_deductions
        // row it paid off (full traceability), and that link is what makes this idempotent:
        // regenerating the same payslip reuses the already-linked amount instead of taking
        // a second installment.
        $loanResult    = $this->resolveLoanRepayments($employee, $payslip);
        $advanceResult = $this->resolveAdvanceDeductions($employee, $payslip);
        $loanDeduct    = round($loanResult['total'] + $advanceResult['total'], 2);

        if ($loanDeduct > 0) {
            $totalDeductions = round($totalDeductions + $loanDeduct, 2);
            $netPay          = round($netPay - $loanDeduct, 2);

            $payslip->update([
                'other_deductions' => round($payslip->other_deductions + $loanDeduct, 2),
                'total_deductions' => $totalDeductions,
                'net_pay'          => $netPay,
                'ytd_net'          => $netPay,
            ]);
        }

        $lines = [];
        // Computed lines — not tied to a salary_component_id since they're derived
        // (base salary, attendance, tax brackets), not read from the catalog.
        foreach (['Base Pay' => $basePay, 'Overtime Pay' => $otPay] as $label => $amount) {
            if ($amount > 0) $lines[] = ['label' => $label, 'type' => 'earning', 'amount' => $amount];
        }
        // One line per assigned earning component — the real breakdown, e.g. "Bonus",
        // "Commission", "Holiday Pay" each shown separately instead of lumped together.
        foreach ($earningComponents as $ec) {
            if ((float) $ec->amount > 0) {
                $lines[] = ['label' => $ec->component->name, 'type' => 'earning', 'amount' => (float) $ec->amount, 'salary_component_id' => $ec->salary_component_id];
            }
        }
        foreach ($reimbursementComponents as $ec) {
            if ((float) $ec->amount > 0) {
                $lines[] = ['label' => $ec->component->name, 'type' => 'reimbursement', 'amount' => (float) $ec->amount, 'salary_component_id' => $ec->salary_component_id];
            }
        }
        foreach ([
            'FED_TAX'   => ['Federal Income Tax', $federalTax],
            'STATE_TAX' => ['State Tax', $stateTax],
            'FICA_SS'   => ['Social Security', $ficaSS],
            'FICA_MED'  => ['Medicare', $ficaMedicare],
        ] as $code => [$label, $amount]) {
            if ($amount > 0) {
                $lines[] = [
                    'label' => $label,
                    'type' => 'tax',
                    'amount' => $amount,
                    'salary_component_id' => $taxComponents[$code]->salary_component_id ?? null,
                ];
            }
        }
        if ($absentDeduct > 0) {
            $lines[] = ['label' => 'Absence Deduction', 'type' => 'deduction', 'amount' => $absentDeduct];
        }
        // One line per loan / advance installment actually deducted this period — e.g.
        // "Loan Repayment — LN-2026-000003" — instead of one lumped generic figure.
        foreach ($loanResult['lines'] as $line) {
            $lines[] = $line;
        }
        foreach ($advanceResult['lines'] as $line) {
            $lines[] = $line;
        }
        // One line per assigned deduction component — e.g. "Health Insurance", "401(k)
        // Contribution", "Vision Insurance" each shown separately.
        foreach ($deductionComponents as $ec) {
            if ((float) $ec->amount > 0) {
                $lines[] = ['label' => $ec->component->name, 'type' => 'deduction', 'amount' => (float) $ec->amount, 'salary_component_id' => $ec->salary_component_id];
            }
        }

        $this->writePayslipItems($payslip, $lines);

        return $payslip;
    }

    /**
     * Populate the itemized PayslipItem breakdown for a generated payslip, mirroring the shape
     * PayrollSyncService already writes for third-party-imported payslips (see its own loop over
     * $row['items']) so a payslip looks the same regardless of source. Clears any prior items
     * first since generatePayslip() can be called again for the same run (regenerate).
     *
     * @param array<int, array{label: string, type: string, amount: float, salary_component_id?: int}> $lines
     */
    private function writePayslipItems(Payslip $payslip, array $lines): void
    {
        $payslip->items()->delete();

        foreach ($lines as $sortOrder => $line) {
            PayslipItem::create([
                'payslip_id'           => $payslip->id,
                'salary_component_id'  => $line['salary_component_id'] ?? null,
                'label'                => $line['label'],
                'type'                 => $line['type'],
                'amount'               => $line['amount'],
                'sort_order'           => $sortOrder,
            ]);
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

    /**
     * This employee's currently-in-effect assigned salary components — active,
     * and (if set) within their effective_from/effective_to date window.
     * Filtered in-memory since Employee::salaryComponents is already eager-loaded
     * by generateRun(), avoiding an N+1 query per employee.
     */
    private function assignedComponents(Employee $employee)
    {
        $today = now()->toDateString();

        return $employee->salaryComponents->filter(function ($ec) use ($today) {
            if (! $ec->is_active || ! $ec->component) {
                return false;
            }
            if ($ec->effective_from && $ec->effective_from->toDateString() > $today) {
                return false;
            }
            if ($ec->effective_to && $ec->effective_to->toDateString() < $today) {
                return false;
            }
            return true;
        });
    }

    /**
     * Deducts this period's due loan installment(s) for every loan this employee has
     * disbursed/active, marking the correct loan_repayments row 'paid' and linking it to
     * this payslip. Idempotent: if this payslip already has a repayment linked to it for a
     * given loan (a regenerate), that existing amount is reused rather than taking another
     * installment or touching the loan's totals a second time.
     *
     * @return array{total: float, lines: array<int, array{label: string, type: string, amount: float}>}
     */
    private function resolveLoanRepayments(Employee $employee, Payslip $payslip): array
    {
        $total = 0.0;
        $lines = [];

        // Queried directly rather than relying on Employee::loans() as eager-loaded by the
        // caller: that relation is filtered to disbursed/active only (so a brand-new loan is
        // picked up for its first deduction), but a loan that completed on an earlier payslip
        // still needs to be found here when that payslip is regenerated — otherwise its
        // already-linked repayment would silently vanish from the recalculated totals.
        $loans = Loan::where('employee_id', $employee->id)
            ->where(function ($q) use ($payslip) {
                $q->whereIn('status', ['disbursed', 'active'])
                  ->orWhereHas('repayments', fn ($r) => $r->where('payslip_id', $payslip->id));
            })
            ->get();

        foreach ($loans as $loan) {
            $existing = $loan->repayments()->where('payslip_id', $payslip->id)->first();

            if ($existing) {
                $amount = (float) $existing->amount_paid;
            } elseif ($loan->auto_deduct_from_payroll && $loan->isActive()) {
                // The next unpaid installment that's actually due by this payslip's pay date —
                // if the loan's schedule doesn't line up with this pay period, nothing is due
                // yet and this loan is simply skipped for this run.
                $installment = $loan->repayments()
                    ->where('status', 'pending')
                    ->where('due_date', '<=', $payslip->pay_date)
                    ->orderBy('installment_number')
                    ->first();

                if (! $installment) {
                    continue;
                }

                $amount = min((float) $installment->emi_amount, (float) $loan->amount_remaining);

                $installment->update([
                    'status'         => 'paid',
                    'paid_date'      => $payslip->pay_date,
                    'amount_paid'    => $amount,
                    'payment_source' => 'payroll_deduction',
                    'payslip_id'     => $payslip->id,
                ]);

                $loan->recalculateTotals();
            } else {
                continue;
            }

            if ($amount > 0) {
                $total += $amount;
                $lines[] = ['label' => "Loan Repayment — {$loan->loan_number}", 'type' => 'deduction', 'amount' => round($amount, 2)];
            }
        }

        return ['total' => round($total, 2), 'lines' => $lines];
    }

    /**
     * Same idea as resolveLoanRepayments() but for salary advances / advance_deductions.
     *
     * @return array{total: float, lines: array<int, array{label: string, type: string, amount: float}>}
     */
    private function resolveAdvanceDeductions(Employee $employee, Payslip $payslip): array
    {
        $total = 0.0;
        $lines = [];

        // See the matching comment in resolveLoanRepayments() — queried directly for the
        // same reason: a completed advance must still be found when re-generating a payslip
        // that already deducted its final installment.
        $advances = SalaryAdvance::where('employee_id', $employee->id)
            ->where(function ($q) use ($payslip) {
                $q->whereIn('status', ['disbursed', 'active'])
                  ->orWhereHas('deductions', fn ($d) => $d->where('payslip_id', $payslip->id));
            })
            ->get();

        foreach ($advances as $advance) {
            $existing = $advance->deductions()->where('payslip_id', $payslip->id)->first();

            if ($existing) {
                $amount = (float) $existing->amount;
            } elseif ($advance->auto_deduct_from_payroll && $advance->isActive()) {
                $deduction = $advance->deductions()
                    ->where('status', 'pending')
                    ->where('deduction_date', '<=', $payslip->pay_date)
                    ->orderBy('deduction_number')
                    ->first();

                if (! $deduction) {
                    continue;
                }

                $amount = min((float) $deduction->amount, (float) $advance->amount_remaining);

                $deduction->update([
                    'status'     => 'deducted',
                    'amount'     => $amount,
                    'payslip_id' => $payslip->id,
                ]);

                $advance->recalculateTotals();
            } else {
                continue;
            }

            if ($amount > 0) {
                $total += $amount;
                $lines[] = ['label' => "Salary Advance — {$advance->advance_number}", 'type' => 'deduction', 'amount' => round($amount, 2)];
            }
        }

        return ['total' => round($total, 2), 'lines' => $lines];
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