<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Http\Resources\LoanTypeResource;
use App\Http\Resources\SalaryAdvanceResource;
use App\Models\Tenant\Loan;
use App\Models\Tenant\LoanRepayment;
use App\Models\Tenant\LoanType;
use App\Models\Tenant\SalaryAdvance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * JSON mirror of Employee/LoanController — same eligibility rules (service
 * months, salary minimums, amount/tenure bounds, cooldowns), same EMI math,
 * covers both Loans and Salary Advances.
 */
class LoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $loans = Loan::with('loanType')
            ->where('employee_id', $emp->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'loans_page');

        $loanStats = Loan::where('employee_id', $emp->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status IN ('disbursed','active')) as active,
                SUM(status = 'completed') as completed,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active') THEN amount_remaining END),0) as total_outstanding,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active') THEN amount_paid END),0) as total_paid,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active') THEN total_amount END),0) as total_borrowed
            ")
            ->first();

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

        $advances = SalaryAdvance::where('employee_id', $emp->id)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'adv_page');

        $advanceStats = SalaryAdvance::where('employee_id', $emp->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending') as pending,
                SUM(status IN ('disbursed','active','approved')) as active,
                SUM(status = 'completed') as completed,
                COALESCE(SUM(CASE WHEN status IN ('disbursed','active','approved') THEN amount_remaining END),0) as total_outstanding
            ")
            ->first();

        return response()->json([
            'loans' => LoanResource::collection($loans->items()),
            'loans_pagination' => ['current_page' => $loans->currentPage(), 'last_page' => $loans->lastPage(), 'total' => $loans->total()],
            'loan_stats' => [
                'total' => (int) $loanStats->total,
                'pending' => (int) $loanStats->pending,
                'active' => (int) $loanStats->active,
                'completed' => (int) $loanStats->completed,
                'total_outstanding' => (float) $loanStats->total_outstanding,
                'total_paid' => (float) $loanStats->total_paid,
                'total_borrowed' => (float) $loanStats->total_borrowed,
            ],
            'active_loan' => $activeLoan ? new LoanResource($activeLoan) : null,
            'next_emi' => $nextEmi ? [
                'due_date' => $nextEmi->due_date?->toDateString(),
                'emi_amount' => (float) $nextEmi->emi_amount,
                'installment_number' => $nextEmi->installment_number,
            ] : null,
            'advances' => SalaryAdvanceResource::collection($advances->items()),
            'advances_pagination' => ['current_page' => $advances->currentPage(), 'last_page' => $advances->lastPage(), 'total' => $advances->total()],
            'advance_stats' => [
                'total' => (int) $advanceStats->total,
                'pending' => (int) $advanceStats->pending,
                'active' => (int) $advanceStats->active,
                'completed' => (int) $advanceStats->completed,
                'total_outstanding' => (float) $advanceStats->total_outstanding,
            ],
        ]);
    }

    public function loanTypes(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $serviceMonths = $emp->service_months ?? 0;
        $baseSalary = (float) ($emp->base_salary ?? 0);

        $types = LoanType::query()
            ->when(Schema::connection('tenant')->hasColumn('loan_types', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(function ($t) use ($serviceMonths, $baseSalary) {
                $minService = (int) ($t->min_service_months ?? 0);
                $minSalary = (float) ($t->min_salary ?? 0);

                $reasons = [];
                if ($minService > 0 && $serviceMonths < $minService) {
                    $reasons[] = "Requires {$minService} months of service (you have {$serviceMonths})";
                }
                if ($minSalary > 0 && $baseSalary > 0 && $baseSalary < $minSalary) {
                    $reasons[] = 'Minimum salary required: '.number_format($minSalary, 0);
                }

                $maxByMultiplier = $baseSalary > 0 && $t->max_salary_multiplier > 0
                    ? round($baseSalary * (float) $t->max_salary_multiplier, 2)
                    : null;

                $effectiveMax = $t->max_amount;
                if ($maxByMultiplier !== null) {
                    $effectiveMax = $effectiveMax ? min((float) $effectiveMax, $maxByMultiplier) : $maxByMultiplier;
                }

                $t->_eligible = empty($reasons);
                $t->_block_reasons = $reasons;
                $t->_effective_max = $effectiveMax;

                return $t;
            });

        return response()->json([
            'loan_types' => LoanTypeResource::collection($types),
            'service_months' => $serviceMonths,
            'base_salary' => $baseSalary,
        ]);
    }

    public function storeLoan(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $data = $request->validate([
            'loan_type_id' => ['required', 'integer', Rule::exists('loan_types', 'id')],
            'principal_amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'tenure_months' => ['required', 'integer', 'min:1', 'max:120'],
            'purpose' => ['required', 'string', 'min:10', 'max:1000'],
            'first_emi_date' => ['required', 'date', 'after:today'],
            'guarantor_external_name' => ['nullable', 'string', 'max:200'],
            'guarantor_external_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $type = LoanType::findOrFail($data['loan_type_id']);

        $serviceMonths = $emp->service_months ?? 0;
        $baseSalary = (float) ($emp->base_salary ?? 0);
        $principal = (float) $data['principal_amount'];
        $tenure = (int) $data['tenure_months'];

        $minService = (int) ($type->min_service_months ?? 0);
        if ($minService > 0 && $serviceMonths < $minService) {
            return $this->validationError('loan_type_id', "You need at least {$minService} months of service to apply for this loan (you have {$serviceMonths}).");
        }

        $minSalary = (float) ($type->min_salary ?? 0);
        if ($minSalary > 0 && $baseSalary > 0 && $baseSalary < $minSalary) {
            return $this->validationError('loan_type_id', 'Your base salary does not meet the minimum required for this loan type.');
        }

        if ($type->min_amount && $principal < (float) $type->min_amount) {
            return $this->validationError('principal_amount', "Minimum loan amount for {$type->name} is ".number_format((float) $type->min_amount, 2).'.');
        }

        $effectiveMax = $type->max_amount ? (float) $type->max_amount : null;
        if ($baseSalary > 0 && $type->max_salary_multiplier > 0) {
            $maxByMultiplier = $baseSalary * (float) $type->max_salary_multiplier;
            $effectiveMax = $effectiveMax ? min($effectiveMax, $maxByMultiplier) : $maxByMultiplier;
        }
        if ($effectiveMax !== null && $principal > $effectiveMax) {
            return $this->validationError('principal_amount', 'Maximum loan amount available to you is '.number_format($effectiveMax, 2).'.');
        }

        $minTenure = (int) ($type->min_tenure_months ?? 1);
        $maxTenure = (int) ($type->max_tenure_months ?? 120);
        if ($tenure < $minTenure || $tenure > $maxTenure) {
            return $this->validationError('tenure_months', "Tenure must be between {$minTenure} and {$maxTenure} months for {$type->name}.");
        }

        if (($type->cooldown_months ?? 0) > 0) {
            $cooldown = (int) $type->cooldown_months;
            $recent = Loan::where('employee_id', $emp->id)
                ->where('loan_type_id', $type->id)
                ->whereIn('status', ['approved', 'disbursed', 'active', 'completed'])
                ->where('created_at', '>=', now()->subMonths($cooldown))
                ->exists();
            if ($recent) {
                return $this->validationError('loan_type_id', "You must wait {$cooldown} months between applications for this loan type.");
            }
        }

        $rate = (float) ($type->default_interest_rate ?? 0);
        $emiData = $this->calculateEmi($principal, $rate, $tenure);

        DB::connection('tenant')->beginTransaction();
        try {
            $loan = Loan::create([
                'employee_id' => $emp->id,
                'loan_type_id' => $type->id,
                'loan_number' => Loan::generateNumber(),
                'principal_amount' => $principal,
                'interest_rate' => $rate,
                'interest_type' => $type->interest_type ?? 'reducing_balance',
                'total_interest' => $emiData['total_interest'],
                'total_amount' => $emiData['total_amount'],
                'processing_fee' => 0,
                'tenure_months' => $tenure,
                'emi_amount' => $emiData['emi'],
                'first_emi_date' => $data['first_emi_date'],
                'amount_paid' => 0,
                'amount_remaining' => $emiData['total_amount'],
                'installments_paid' => 0,
                'installments_remaining' => $tenure,
                'purpose' => $data['purpose'],
                'guarantor_external_name' => $data['guarantor_external_name'] ?? null,
                'guarantor_external_phone' => $data['guarantor_external_phone'] ?? null,
                'status' => 'pending',
                'requested_by' => $request->user()->id,
                'requested_at' => now(),
                'auto_deduct_from_payroll' => (bool) ($type->auto_deduct_from_payroll ?? true),
            ]);

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Loan store failed (API): '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());

            return response()->json(['success' => false, 'message' => 'Could not submit your loan application. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Loan application {$loan->loan_number} submitted. Your manager will review it soon.",
            'loan' => new LoanResource($loan->load('loanType')),
        ]);
    }

    public function showLoan(Request $request, int $loan): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $ln = Loan::with(['loanType', 'guarantorEmployee', 'repayments' => fn ($q) => $q->orderBy('installment_number')])
            ->where('employee_id', $emp->id)
            ->findOrFail($loan);

        $ln->timeline = $this->buildLoanTimeline($ln);

        return response()->json(['loan' => new LoanResource($ln)]);
    }

    public function cancelLoan(Request $request, int $loan): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $ln = Loan::where('employee_id', $emp->id)->findOrFail($loan);

        if ($ln->status !== 'pending') {
            return $this->fail('Only pending loan applications can be cancelled.');
        }

        $ln->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => "Loan application {$ln->loan_number} cancelled."]);
    }

    public function storeAdvance(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $hasActiveAdvance = SalaryAdvance::where('employee_id', $emp->id)
            ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
            ->exists();

        if ($hasActiveAdvance) {
            return $this->fail('You already have an active advance request. Please wait for it to complete before applying again.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'repayment_type' => ['required', Rule::in(['one_time', 'installments'])],
            'installments_count' => ['required_if:repayment_type,installments', 'nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $amount = (float) $data['amount'];
        $baseSalary = (float) ($emp->base_salary ?? 0);
        if ($baseSalary > 0 && $amount > $baseSalary) {
            return $this->validationError('amount', 'Advance cannot exceed your monthly salary ('.number_format($baseSalary, 2).').');
        }

        $months = $data['repayment_type'] === 'installments' ? (int) ($data['installments_count'] ?? 1) : 1;
        $perInstallment = round($amount / $months, 2);

        DB::connection('tenant')->beginTransaction();
        try {
            $adv = SalaryAdvance::create([
                'employee_id' => $emp->id,
                'advance_number' => SalaryAdvance::generateNumber(),
                'amount' => $amount,
                'reason' => $data['reason'],
                'repayment_type' => $data['repayment_type'],
                'installments_count' => $months,
                'per_installment_amount' => $perInstallment,
                'first_deduction_date' => now()->addMonth()->startOfMonth(),
                'amount_repaid' => 0,
                'amount_remaining' => $amount,
                'installments_paid' => 0,
                'status' => 'pending',
            ]);

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Advance store failed (API): '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not submit your advance request. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Advance request {$adv->advance_number} submitted. Your manager will review it soon.",
            'advance' => new SalaryAdvanceResource($adv),
        ]);
    }

    public function showAdvance(Request $request, int $advance): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $adv = SalaryAdvance::with(['deductions' => fn ($q) => $q->orderBy('deduction_number')])
            ->where('employee_id', $emp->id)
            ->findOrFail($advance);

        $adv->timeline = $this->buildAdvanceTimeline($adv);

        return response()->json(['advance' => new SalaryAdvanceResource($adv)]);
    }

    public function cancelAdvance(Request $request, int $advance): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $adv = SalaryAdvance::where('employee_id', $emp->id)->findOrFail($advance);

        if ($adv->status !== 'pending') {
            return $this->fail('Only pending advance requests can be cancelled.');
        }

        $adv->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => "Advance request {$adv->advance_number} cancelled."]);
    }

    public function calculateEmiEndpoint(Request $request): JsonResponse
    {
        $request->validate([
            'principal' => 'required|numeric|min:1',
            'tenure_months' => 'required|integer|min:1|max:120',
            'interest_rate' => 'nullable|numeric|min:0',
        ]);

        return response()->json($this->calculateEmi(
            (float) $request->principal,
            (float) ($request->interest_rate ?? 0),
            (int) $request->tenure_months
        ));
    }

    protected function employeeOrFail(Request $request)
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return $emp;
    }

    protected function fail(string $msg, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $msg], $status);
    }

    protected function validationError(string $field, string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
    }

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

        return ['emi' => $emi, 'total_amount' => $total, 'total_interest' => $interest];
    }

    protected function buildLoanTimeline(Loan $loan): array
    {
        $events = [];
        $events[] = ['icon' => 'file-plus', 'color' => 'brand', 'title' => 'Application submitted', 'detail' => null, 'when' => $loan->requested_at ?? $loan->created_at];

        if ($loan->approved_at) {
            $events[] = $loan->status === 'rejected'
                ? ['icon' => 'x-circle', 'color' => 'red', 'title' => 'Application rejected', 'detail' => $loan->rejection_reason, 'when' => $loan->approved_at]
                : ['icon' => 'check-circle', 'color' => 'green', 'title' => 'Application approved', 'detail' => $loan->approver_comments, 'when' => $loan->approved_at];
        }

        if ($loan->disbursed_at) {
            $events[] = [
                'icon' => 'banknote', 'color' => 'green', 'title' => 'Loan disbursed',
                'detail' => $loan->disbursement_method
                    ? 'Via '.str_replace('_', ' ', $loan->disbursement_method).($loan->disbursement_reference ? ' (ref: '.$loan->disbursement_reference.')' : '')
                    : null,
                'when' => $loan->disbursed_at,
            ];
        }

        if ($loan->completed_at) {
            $events[] = ['icon' => 'flag', 'color' => 'green', 'title' => 'Loan fully repaid', 'detail' => 'All installments completed', 'when' => $loan->completed_at];
        }

        if ($loan->status === 'cancelled') {
            $events[] = ['icon' => 'ban', 'color' => 'gray', 'title' => 'Application cancelled', 'detail' => null, 'when' => $loan->updated_at];
        }

        usort($events, fn ($a, $b) => Carbon::parse($a['when'])->timestamp <=> Carbon::parse($b['when'])->timestamp);
        foreach ($events as &$e) $e['when'] = Carbon::parse($e['when'])->toIso8601String();

        return $events;
    }

    protected function buildAdvanceTimeline(SalaryAdvance $adv): array
    {
        $events = [];
        $events[] = ['icon' => 'file-plus', 'color' => 'brand', 'title' => 'Advance requested', 'detail' => null, 'when' => $adv->created_at];

        if ($adv->approved_at) {
            $events[] = $adv->status === 'rejected'
                ? ['icon' => 'x-circle', 'color' => 'red', 'title' => 'Request rejected', 'detail' => $adv->rejection_reason, 'when' => $adv->approved_at]
                : ['icon' => 'check-circle', 'color' => 'green', 'title' => 'Request approved', 'detail' => $adv->approver_comments, 'when' => $adv->approved_at];
        }

        if ($adv->disbursed_at) {
            $events[] = ['icon' => 'banknote', 'color' => 'green', 'title' => 'Advance disbursed', 'detail' => null, 'when' => $adv->disbursed_at];
        }

        if ($adv->completed_at) {
            $events[] = ['icon' => 'flag', 'color' => 'green', 'title' => 'Fully repaid', 'detail' => null, 'when' => $adv->completed_at];
        }

        if ($adv->status === 'cancelled') {
            $events[] = ['icon' => 'ban', 'color' => 'gray', 'title' => 'Request cancelled', 'detail' => null, 'when' => $adv->updated_at];
        }

        usort($events, fn ($a, $b) => Carbon::parse($a['when'])->timestamp <=> Carbon::parse($b['when'])->timestamp);
        foreach ($events as &$e) $e['when'] = Carbon::parse($e['when'])->toIso8601String();

        return $events;
    }
}
