<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Loan;
use App\Models\Tenant\LoanRepayment;
use App\Models\Tenant\LoanType;
use App\Models\Tenant\SalaryAdvance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    /* ================================================================
     | INDEX — combined Loans + Advances dashboard
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $tab = $request->get('tab', 'loans');

        /* ───────── LOANS ───────── */
        $loans = Loan::with('loanType')
            ->where('employee_id', $emp->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'loans_page')
            ->withQueryString();

        $loanStats = Loan::where('employee_id', $emp->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending')                     as pending,
                SUM(status IN ('disbursed','active'))       as active,
                SUM(status = 'completed')                   as completed,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active') THEN amount_remaining END),0) as total_outstanding,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active') THEN amount_paid END),0)      as total_paid,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active') THEN total_amount END),0)     as total_borrowed
            ")
            ->first();

        // Active loan with next EMI date (top of dashboard)
        $activeLoan = Loan::with('loanType')
            ->where('employee_id', $emp->id)
            ->whereIn('status', ['disbursed', 'active'])
            ->orderByDesc('disbursed_at')
            ->first();

        $nextEmi = null;
        if ($activeLoan) {
            $nextEmi = LoanRepayment::where('loan_id', $activeLoan->id)
                ->whereIn('status', ['pending', 'overdue'])
                ->orderBy('due_date')
                ->first();
        }

        /* ───────── ADVANCES ───────── */
        $advances = SalaryAdvance::where('employee_id', $emp->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'adv_page')
            ->withQueryString();

        $advanceStats = SalaryAdvance::where('employee_id', $emp->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending')                              as pending,
                SUM(status IN ('disbursed','active','approved'))     as active,
                SUM(status = 'completed')                            as completed,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active','approved') THEN amount_remaining END),0) as total_outstanding
            ")
            ->first();

        return view('employee.loans.index', [
            'tenantSlug'    => $tenant,
            'emp'           => $emp,
            'tab'           => $tab,
            'loans'         => $loans,
            'loanStats'     => $loanStats,
            'activeLoan'    => $activeLoan,
            'nextEmi'       => $nextEmi,
            'advances'      => $advances,
            'advanceStats'  => $advanceStats,
        ]);
    }

    /* ================================================================
     | LOAN — APPLY FORM
     |================================================================*/
    public function createLoan(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        // Build eligibility-checked loan types
        $serviceMonths = $emp->service_months ?? 0;
        $baseSalary    = (float) ($emp->base_salary ?? 0);

        $types = LoanType::query()
            ->when(Schema::connection('tenant')->hasColumn('loan_types', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(function ($t) use ($serviceMonths, $baseSalary) {
                $minService = (int) ($t->min_service_months ?? 0);
                $minSalary  = (float) ($t->min_salary ?? 0);

                $reasons = [];
                if ($minService > 0 && $serviceMonths < $minService) {
                    $reasons[] = "Requires {$minService} months of service (you have {$serviceMonths})";
                }
                if ($minSalary > 0 && $baseSalary > 0 && $baseSalary < $minSalary) {
                    $reasons[] = "Minimum salary required: " . number_format($minSalary, 0);
                }

                $maxByMultiplier = $baseSalary > 0 && $t->max_salary_multiplier > 0
                    ? round($baseSalary * (float) $t->max_salary_multiplier, 2)
                    : null;

                $effectiveMax = $t->max_amount;
                if ($maxByMultiplier !== null) {
                    $effectiveMax = $effectiveMax
                        ? min((float) $effectiveMax, $maxByMultiplier)
                        : $maxByMultiplier;
                }

                $t->_eligible      = empty($reasons);
                $t->_block_reasons = $reasons;
                $t->_effective_max = $effectiveMax;

                return $t;
            });

        return view('employee.loans.create', [
            'tenantSlug'   => $tenant,
            'emp'          => $emp,
            'types'        => $types,
            'serviceMonths'=> $serviceMonths,
            'baseSalary'   => $baseSalary,
        ]);
    }

    /* ================================================================
     | LOAN — STORE
     |================================================================*/
    public function storeLoan(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'loan_type_id'      => ['required', 'integer', Rule::exists('loan_types', 'id')],
            'principal_amount'  => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'tenure_months'     => ['required', 'integer', 'min:1', 'max:120'],
            'purpose'           => ['required', 'string', 'min:10', 'max:1000'],
            'first_emi_date'    => ['required', 'date', 'after:today'],
            'guarantor_external_name'  => ['nullable', 'string', 'max:200'],
            'guarantor_external_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $type = LoanType::findOrFail($data['loan_type_id']);

        // Eligibility checks
        $serviceMonths = $emp->service_months ?? 0;
        $baseSalary    = (float) ($emp->base_salary ?? 0);
        $principal     = (float) $data['principal_amount'];
        $tenure        = (int) $data['tenure_months'];

        $minService = (int) ($type->min_service_months ?? 0);
        if ($minService > 0 && $serviceMonths < $minService) {
            return back()->withInput()->withErrors([
                'loan_type_id' => "You need at least {$minService} months of service to apply for this loan (you have {$serviceMonths}).",
            ]);
        }

        $minSalary = (float) ($type->min_salary ?? 0);
        if ($minSalary > 0 && $baseSalary > 0 && $baseSalary < $minSalary) {
            return back()->withInput()->withErrors([
                'loan_type_id' => "Your base salary does not meet the minimum required for this loan type.",
            ]);
        }

        // Amount bounds
        if ($type->min_amount && $principal < (float) $type->min_amount) {
            return back()->withInput()->withErrors([
                'principal_amount' => "Minimum loan amount for {$type->name} is " . number_format((float) $type->min_amount, 2) . ".",
            ]);
        }

        $effectiveMax = $type->max_amount ? (float) $type->max_amount : null;
        if ($baseSalary > 0 && $type->max_salary_multiplier > 0) {
            $maxByMultiplier = $baseSalary * (float) $type->max_salary_multiplier;
            $effectiveMax = $effectiveMax ? min($effectiveMax, $maxByMultiplier) : $maxByMultiplier;
        }
        if ($effectiveMax !== null && $principal > $effectiveMax) {
            return back()->withInput()->withErrors([
                'principal_amount' => "Maximum loan amount available to you is " . number_format($effectiveMax, 2) . ".",
            ]);
        }

        // Tenure bounds
        $minTenure = (int) ($type->min_tenure_months ?? 1);
        $maxTenure = (int) ($type->max_tenure_months ?? 120);
        if ($tenure < $minTenure || $tenure > $maxTenure) {
            return back()->withInput()->withErrors([
                'tenure_months' => "Tenure must be between {$minTenure} and {$maxTenure} months for {$type->name}.",
            ]);
        }

        // Cooldown check
        if (($type->cooldown_months ?? 0) > 0) {
            $cooldown = (int) $type->cooldown_months;
            $recent = Loan::where('employee_id', $emp->id)
                ->where('loan_type_id', $type->id)
                ->whereIn('status', ['approved', 'disbursed', 'active', 'completed'])
                ->where('created_at', '>=', now()->subMonths($cooldown))
                ->exists();
            if ($recent) {
                return back()->withInput()->withErrors([
                    'loan_type_id' => "You must wait {$cooldown} months between applications for this loan type.",
                ]);
            }
        }

        // EMI calculation
        $rate = (float) ($type->default_interest_rate ?? 0);
        $emiData = $this->calculateEmi($principal, $rate, $tenure);

        DB::connection('tenant')->beginTransaction();
        try {
            $loan = Loan::create([
                'employee_id'           => $emp->id,
                'loan_type_id'          => $type->id,
                'loan_number'           => Loan::generateNumber(),
                'principal_amount'      => $principal,
                'interest_rate'         => $rate,
                'interest_type'         => $type->interest_type ?? 'reducing_balance',
                'total_interest'        => $emiData['total_interest'],
                'total_amount'          => $emiData['total_amount'],
                'processing_fee'        => 0,
                'tenure_months'         => $tenure,
                'emi_amount'            => $emiData['emi'],
                'first_emi_date'        => $data['first_emi_date'],
                'amount_paid'           => 0,
                'amount_remaining'      => $emiData['total_amount'],
                'installments_paid'     => 0,
                'installments_remaining'=> $tenure,
                'purpose'               => $data['purpose'],
                'guarantor_external_name'  => $data['guarantor_external_name'] ?? null,
                'guarantor_external_phone' => $data['guarantor_external_phone'] ?? null,
                'status'                => 'pending',
                'requested_by'          => auth()->id(),
                'requested_at'          => now(),
                'auto_deduct_from_payroll' => (bool) ($type->auto_deduct_from_payroll ?? true),
            ]);

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Employee loan store failed: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return back()->withInput()->with('error', 'Could not submit your loan application. Please try again.');
        }

        return redirect()
            ->route('employee.loans.show', [$tenant, $loan->id])
            ->with('success', "Loan application {$loan->loan_number} submitted. Your manager will review it soon.");
    }

    /* ================================================================
     | LOAN — DETAIL
     |================================================================*/
    public function showLoan(string $tenant, int $loan)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $ln = Loan::with(['loanType', 'approver', 'disburser', 'repayments' => function ($q) {
                $q->orderBy('installment_number');
            }])
            ->where('employee_id', $emp->id)
            ->findOrFail($loan);

        // Mark overdue installments
        $today = now()->startOfDay();
        foreach ($ln->repayments as $r) {
            if ($r->status === 'pending' && $r->due_date && $r->due_date->lt($today)) {
                $r->_is_overdue = true;
            }
        }

        // Progress percentage
        $progress = $ln->total_amount > 0
            ? round(((float) $ln->amount_paid / (float) $ln->total_amount) * 100, 1)
            : 0;

        $timeline = $this->buildLoanTimeline($ln);

        return view('employee.loans.show', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'loan'       => $ln,
            'progress'   => $progress,
            'timeline'   => $timeline,
        ]);
    }

    /* ================================================================
     | LOAN — CANCEL pending loan (own only)
     |================================================================*/
    public function cancelLoan(string $tenant, int $loan)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $ln = Loan::where('employee_id', $emp->id)->findOrFail($loan);

        if ($ln->status !== 'pending') {
            return back()->with('error', 'Only pending loan applications can be cancelled.');
        }

        $ln->update(['status' => 'cancelled']);

        return redirect()
            ->route('employee.loans.index', $tenant)
            ->with('success', "Loan application {$ln->loan_number} cancelled.");
    }

    /* ================================================================
     | ADVANCE — REQUEST FORM
     |================================================================*/
    public function createAdvance(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $baseSalary = (float) ($emp->base_salary ?? 0);

        // Existing active advance — can't have two at once
        $hasActiveAdvance = SalaryAdvance::where('employee_id', $emp->id)
            ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
            ->exists();

        return view('employee.loans.create-advance', [
            'tenantSlug'       => $tenant,
            'emp'              => $emp,
            'baseSalary'       => $baseSalary,
            'hasActiveAdvance' => $hasActiveAdvance,
        ]);
    }

    /* ================================================================
     | ADVANCE — STORE
     |================================================================*/
    public function storeAdvance(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        // Block if there's an existing active advance
        $hasActiveAdvance = SalaryAdvance::where('employee_id', $emp->id)
            ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
            ->exists();

        if ($hasActiveAdvance) {
            return back()->withInput()->with('error', 'You already have an active advance request. Please wait for it to complete before applying again.');
        }

        $data = $request->validate([
            'amount'             => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'reason'             => ['required', 'string', 'min:10', 'max:1000'],
            'repayment_type'     => ['required', Rule::in(['one_time', 'installments'])],
            'installments_count' => ['required_if:repayment_type,installments', 'nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $amount = (float) $data['amount'];

        // Cap at 1× monthly salary if salary is set
        $baseSalary = (float) ($emp->base_salary ?? 0);
        if ($baseSalary > 0 && $amount > $baseSalary) {
            return back()->withInput()->withErrors([
                'amount' => "Advance cannot exceed your monthly salary (" . number_format($baseSalary, 2) . ").",
            ]);
        }

        $months = $data['repayment_type'] === 'installments' ? (int) ($data['installments_count'] ?? 1) : 1;
        $perInstallment = round($amount / $months, 2);

        DB::connection('tenant')->beginTransaction();
        try {
            $adv = SalaryAdvance::create([
                'employee_id'            => $emp->id,
                'advance_number'         => SalaryAdvance::generateNumber(),
                'amount'                 => $amount,
                'reason'                 => $data['reason'],
                'repayment_type'         => $data['repayment_type'],
                'installments_count'     => $months,
                'per_installment_amount' => $perInstallment,
                'first_deduction_date'   => now()->addMonth()->startOfMonth(),
                'amount_repaid'          => 0,
                'amount_remaining'       => $amount,
                'installments_paid'      => 0,
                'status'                 => 'pending',
            ]);

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Employee advance store failed: '.$e->getMessage());
            return back()->withInput()->with('error', 'Could not submit your advance request. Please try again.');
        }

        return redirect()
            ->route('employee.loans.advance.show', [$tenant, $adv->id])
            ->with('success', "Advance request {$adv->advance_number} submitted. Your manager will review it soon.");
    }

    /* ================================================================
     | ADVANCE — DETAIL
     |================================================================*/
    public function showAdvance(string $tenant, int $advance)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $adv = SalaryAdvance::with(['approver', 'deductions' => function ($q) {
                $q->orderBy('deduction_number');
            }])
            ->where('employee_id', $emp->id)
            ->findOrFail($advance);

        $progress = $adv->amount > 0
            ? round(((float) $adv->amount_repaid / (float) $adv->amount) * 100, 1)
            : 0;

        $timeline = $this->buildAdvanceTimeline($adv);

        return view('employee.loans.show-advance', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'advance'    => $adv,
            'progress'   => $progress,
            'timeline'   => $timeline,
        ]);
    }

    /* ================================================================
     | ADVANCE — CANCEL pending advance
     |================================================================*/
    public function cancelAdvance(string $tenant, int $advance)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $adv = SalaryAdvance::where('employee_id', $emp->id)->findOrFail($advance);

        if ($adv->status !== 'pending') {
            return back()->with('error', 'Only pending advance requests can be cancelled.');
        }

        $adv->update(['status' => 'cancelled']);

        return redirect()
            ->route('employee.loans.index', $tenant)
            ->with('success', "Advance request {$adv->advance_number} cancelled.");
    }

    /* ================================================================
     | LIVE EMI CALCULATOR (AJAX endpoint for form)
     |================================================================*/
    public function calculateEmiEndpoint(string $tenant, Request $request)
    {
        $request->validate([
            'principal'     => 'required|numeric|min:1',
            'tenure_months' => 'required|integer|min:1|max:120',
            'interest_rate' => 'nullable|numeric|min:0',
        ]);

        $emi = $this->calculateEmi(
            (float) $request->principal,
            (float) ($request->interest_rate ?? 0),
            (int) $request->tenure_months
        );

        return response()->json($emi);
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    protected function calculateEmi(float $principal, float $annualRate, int $tenureMonths): array
    {
        if ($annualRate > 0 && $tenureMonths > 0) {
            $r = $annualRate / 100 / 12;
            $emi = $principal * $r * pow(1 + $r, $tenureMonths) / (pow(1 + $r, $tenureMonths) - 1);
            $emi = round($emi, 2);
            $total = round($emi * $tenureMonths, 2);
            $interest = round($total - $principal, 2);
        } else {
            $emi = $tenureMonths > 0 ? round($principal / $tenureMonths, 2) : 0;
            $total = $principal;
            $interest = 0;
        }

        return [
            'emi'            => $emi,
            'total_amount'   => $total,
            'total_interest' => $interest,
        ];
    }

    protected function buildLoanTimeline(Loan $loan): array
    {
        $events = [];

        $events[] = [
            'icon'   => 'file-plus',
            'color'  => 'brand',
            'title'  => 'Application submitted',
            'detail' => null,
            'when'   => $loan->requested_at ?? $loan->created_at,
        ];

        if ($loan->approved_at) {
            if ($loan->status === 'rejected') {
                $events[] = [
                    'icon'   => 'x-circle',
                    'color'  => 'red',
                    'title'  => 'Application rejected',
                    'detail' => $loan->rejection_reason,
                    'when'   => $loan->approved_at,
                ];
            } else {
                $events[] = [
                    'icon'   => 'check-circle',
                    'color'  => 'green',
                    'title'  => 'Application approved',
                    'detail' => $loan->approver_comments,
                    'when'   => $loan->approved_at,
                ];
            }
        }

        if ($loan->disbursed_at) {
            $events[] = [
                'icon'   => 'banknote',
                'color'  => 'green',
                'title'  => 'Loan disbursed',
                'detail' => $loan->disbursement_method
                    ? 'Via ' . str_replace('_', ' ', $loan->disbursement_method)
                        . ($loan->disbursement_reference ? ' (ref: ' . $loan->disbursement_reference . ')' : '')
                    : null,
                'when'   => $loan->disbursed_at,
            ];
        }

        if ($loan->completed_at) {
            $events[] = [
                'icon'   => 'flag',
                'color'  => 'green',
                'title'  => 'Loan fully repaid',
                'detail' => 'All installments completed',
                'when'   => $loan->completed_at,
            ];
        }

        if ($loan->status === 'cancelled') {
            $events[] = [
                'icon'   => 'ban',
                'color'  => 'gray',
                'title'  => 'Application cancelled',
                'detail' => null,
                'when'   => $loan->updated_at,
            ];
        }

        usort($events, fn ($a, $b) => Carbon::parse($a['when'])->timestamp <=> Carbon::parse($b['when'])->timestamp);

        return $events;
    }

    protected function buildAdvanceTimeline(SalaryAdvance $adv): array
    {
        $events = [];

        $events[] = [
            'icon'   => 'file-plus',
            'color'  => 'brand',
            'title'  => 'Advance requested',
            'detail' => null,
            'when'   => $adv->created_at,
        ];

        if ($adv->approved_at) {
            if ($adv->status === 'rejected') {
                $events[] = [
                    'icon'   => 'x-circle',
                    'color'  => 'red',
                    'title'  => 'Request rejected',
                    'detail' => $adv->rejection_reason,
                    'when'   => $adv->approved_at,
                ];
            } else {
                $events[] = [
                    'icon'   => 'check-circle',
                    'color'  => 'green',
                    'title'  => 'Request approved',
                    'detail' => $adv->approver_comments,
                    'when'   => $adv->approved_at,
                ];
            }
        }

        if ($adv->disbursed_at) {
            $events[] = [
                'icon'   => 'banknote',
                'color'  => 'green',
                'title'  => 'Advance disbursed',
                'detail' => null,
                'when'   => $adv->disbursed_at,
            ];
        }

        if ($adv->completed_at) {
            $events[] = [
                'icon'   => 'flag',
                'color'  => 'green',
                'title'  => 'Fully repaid',
                'detail' => null,
                'when'   => $adv->completed_at,
            ];
        }

        if ($adv->status === 'cancelled') {
            $events[] = [
                'icon'   => 'ban',
                'color'  => 'gray',
                'title'  => 'Request cancelled',
                'detail' => null,
                'when'   => $adv->updated_at,
            ];
        }

        usort($events, fn ($a, $b) => Carbon::parse($a['when'])->timestamp <=> Carbon::parse($b['when'])->timestamp);

        return $events;
    }
}