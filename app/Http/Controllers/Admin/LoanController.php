<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Loan;
use App\Models\Tenant\LoanRepayment;
use App\Models\Tenant\LoanType;
use App\Models\Tenant\SalaryAdvance;
use App\Services\ExportService;
use App\Services\MailService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /* ================================================================
     | INDEX — dashboard
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $tab = $request->get('tab', 'loans');

        // ---- LOANS ----
        $loanQuery = Loan::with(['employee.department', 'loanType'])
            ->latest();

        if ($status = $request->get('status')) {
            $loanQuery->where('status', $status);
        }
        if ($emp = $request->get('employee')) {
            $loanQuery->where('employee_id', $emp);
        }

        $loans = $loanQuery->paginate(15, ['*'], 'loans_page')->withQueryString();

        // ---- ADVANCES ----
        $advanceQuery = SalaryAdvance::with(['employee.department'])
            ->latest();

        if ($advStatus = $request->get('adv_status')) {
            $advanceQuery->where('status', $advStatus);
        }

        $advances = $advanceQuery->paginate(15, ['*'], 'adv_page')->withQueryString();

        // ---- STATS ----
        $stats = [
            'active_loans'     => Loan::whereIn('status', ['disbursed','active'])->count(),
            'total_outstanding'=> Loan::whereIn('status', ['disbursed','active'])->sum('amount_remaining'),
            'pending_loans'    => Loan::where('status', 'pending')->count(),
            'active_advances'  => SalaryAdvance::where('status', 'active')->count(),
        ];

        // ---- LOAN TYPES ----
        $loanTypes = LoanType::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();

        return view('admin.loans.index', compact(
            'loans', 'advances', 'loanTypes', 'employees',
            'stats', 'tab', 'tenant'
        ));
    }

    /* ================================================================
     | SHOW LOAN
     |================================================================*/
    public function show(string $tenant, Loan $loan)
    {
        $loan->load(['employee.department', 'loanType', 'repayments', 'approver']);
        return view('admin.loans.show', compact('loan', 'tenant'));
    }

    /* ================================================================
     | STORE LOAN
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'loan_type_id'  => 'required|exists:loan_types,id',
            'principal_amount' => 'required|numeric|min:100',
            'tenure_months' => 'required|integer|min:1|max:120',
            'purpose'       => 'nullable|string|max:500',
            'first_emi_date'=> 'required|date',
        ]);

        $loanType    = LoanType::findOrFail($data['loan_type_id']);
        $principal   = (float) $data['principal_amount'];
        $rate        = (float) $loanType->default_interest_rate;
        $tenure      = (int) $data['tenure_months'];

        // Calculate EMI (reducing balance)
        if ($rate > 0) {
            $monthlyRate = $rate / 100 / 12;
            $emi = round(
                $principal * $monthlyRate * pow(1 + $monthlyRate, $tenure)
                / (pow(1 + $monthlyRate, $tenure) - 1),
                2
            );
            $totalAmount   = round($emi * $tenure, 2);
            $totalInterest = round($totalAmount - $principal, 2);
        } else {
            $emi           = round($principal / $tenure, 2);
            $totalAmount   = $principal;
            $totalInterest = 0;
        }

        $loan = Loan::create([
            'employee_id'          => $data['employee_id'],
            'loan_type_id'         => $data['loan_type_id'],
            'loan_number'          => Loan::generateNumber(),
            'principal_amount'     => $principal,
            'interest_rate'        => $rate,
            'interest_type'        => $loanType->interest_type ?? 'reducing',
            'total_interest'       => $totalInterest,
            'total_amount'         => $totalAmount,
            'tenure_months'        => $tenure,
            'emi_amount'           => $emi,
            'first_emi_date'       => $data['first_emi_date'],
            'amount_remaining'     => $totalAmount,
            'amount_paid'          => 0,
            'installments_paid'    => 0,
            'installments_remaining'=> $tenure,
            'purpose'              => $data['purpose'] ?? null,
            'status'               => 'pending',
            'requested_by'         => auth()->id(),
            'requested_at'         => now(),
            'auto_deduct_from_payroll' => true,
        ]);

        return redirect()
            ->route('admin.loans.show', [$tenant, $loan->id])
            ->with('success', "Loan {$loan->loan_number} created.");
    }

    /* ================================================================
     | APPROVE LOAN
     |================================================================*/
    public function approve(string $tenant, Loan $loan)
    {
        if ($loan->status !== 'pending') {
            return back()->with('error', 'Only pending loans can be approved.');
        }

        $loan->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $loan = $loan->fresh(['employee', 'loanType']);
        app(MailService::class)->sendLoanApproved($loan);
        if ($loan->employee?->user) {
            NotificationService::loanApproved($loan->employee->user,
                number_format((float) $loan->principal_amount, 2),
                route('employee.loans.index', $tenant));
        }

        return back()->with('success', "Loan {$loan->loan_number} approved.");
    }

    /* ================================================================
     | DISBURSE LOAN
     |================================================================*/
    public function disburse(string $tenant, Request $request, Loan $loan)
    {
        $request->validate([
            'disbursement_method'    => 'required|string',
            'disbursement_reference' => 'nullable|string|max:100',
        ]);

        if ($loan->status !== 'approved') {
            return back()->with('error', 'Only approved loans can be disbursed.');
        }

        $loan->update([
            'status'                  => 'disbursed',
            'disbursed_by'            => auth()->id(),
            'disbursed_at'            => now(),
            'disbursement_method'     => $request->disbursement_method,
            'disbursement_reference'  => $request->disbursement_reference,
        ]);

        // Generate repayment schedule
        $this->generateRepaymentSchedule($loan);

        $loan = $loan->fresh(['employee', 'loanType']);
        app(MailService::class)->sendLoanDisbursed($loan);
        if ($loan->employee?->user) {
            NotificationService::loanDisbursed($loan->employee->user,
                number_format((float) $loan->principal_amount, 2),
                route('employee.loans.index', $tenant));
        }

        return back()->with('success', "Loan disbursed. Repayment schedule generated.");
    }

    /* ================================================================
     | REJECT LOAN
     |================================================================*/
    public function reject(string $tenant, Request $request, Loan $loan)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $loan->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);

        $loan = $loan->fresh(['employee', 'loanType']);
        app(MailService::class)->sendLoanRejected($loan);
        if ($loan->employee?->user) {
            NotificationService::loanRejected($loan->employee->user,
                number_format((float) $loan->principal_amount, 2),
                route('employee.loans.index', $tenant));
        }

        return back()->with('success', 'Loan rejected.');
    }

    /* ================================================================
     | RECORD REPAYMENT
     |================================================================*/
    public function recordRepayment(string $tenant, Request $request, Loan $loan)
    {
        $request->validate([
            'installment_id' => 'required|exists:loan_repayments,id',
            'amount_paid'    => 'required|numeric|min:0.01',
            'paid_date'      => 'required|date',
            'payment_source' => 'required|string',
            'reference'      => 'nullable|string|max:100',
        ]);

        $repayment = LoanRepayment::findOrFail($request->installment_id);
        $repayment->update([
            'amount_paid'    => $request->amount_paid,
            'paid_date'      => $request->paid_date,
            'payment_source' => $request->payment_source,
            'reference'      => $request->reference,
            'status'         => 'paid',
            'recorded_by'    => auth()->id(),
        ]);

        // Update loan totals
        $totalPaid = $loan->repayments()->where('status', 'paid')->sum('amount_paid');
        $remaining = max(0, (float)$loan->total_amount - (float)$totalPaid);
        $paidCount = $loan->repayments()->where('status', 'paid')->count();

        $loan->update([
            'amount_paid'            => $totalPaid,
            'amount_remaining'       => $remaining,
            'installments_paid'      => $paidCount,
            'installments_remaining' => $loan->tenure_months - $paidCount,
            'status'                 => $remaining <= 0 ? 'completed' : 'active',
            'completed_at'           => $remaining <= 0 ? now() : null,
        ]);

        return back()->with('success', 'Repayment recorded.');
    }

    /* ================================================================
     | SALARY ADVANCES
     |================================================================*/
public function storeAdvance(string $tenant, Request $request)
{
    $data = $request->validate([
        'employee_id'      => 'required|exists:employees,id',
        'amount'           => 'required|numeric|min:100',
        'reason'           => 'nullable|string|max:500',
        'repayment_months' => 'required|integer|min:1|max:12',
        'requested_date'   => 'required|date',
    ]);

    $amount   = (float) $data['amount'];
    $months   = (int) $data['repayment_months'];
    $perMonth = round($amount / $months, 2);

    SalaryAdvance::create([
        'employee_id'            => $data['employee_id'],
        'advance_number'         => SalaryAdvance::generateNumber(),
        'amount'                 => $amount,
        'amount_remaining'       => $amount,
        'amount_repaid'          => 0,
        'per_installment_amount' => $perMonth, // correct column
        'installments_count' => $months, // correct column
        'installments_paid'      => 0,
        'first_deduction_date'   => Carbon::parse($data['requested_date'])
                                        ->addMonth()->startOfMonth(),
        'reason'                 => $data['reason'] ?? null,
        'status'                 => 'pending',
    ]);

    return back()->with('success', 'Salary advance request created. ($'.number_format($perMonth,2).'/month for '.$months.' months)');
}

    public function approveAdvance(string $tenant, SalaryAdvance $advance)
    {
        $advance->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Salary advance approved.');
    }

    public function rejectAdvance(string $tenant, Request $request, SalaryAdvance $advance)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $advance->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', 'Salary advance rejected.');
    }

    /* ================================================================
     | LOAN TYPES
     |================================================================*/
    public function types(string $tenant)
    {
        $types = LoanType::withCount('loans')->orderBy('name')->get();
        return view('admin.loans.types', compact('types', 'tenant'));
    }

    public function storeType(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'code'                  => 'required|string|max:20|unique:loan_types,code',
            'default_interest_rate' => 'required|numeric|min:0',
            'interest_type'         => 'required|in:flat,reducing',
            'max_amount'            => 'required|numeric|min:0',
            'min_amount'            => 'required|numeric|min:0',
            'max_tenure_months'     => 'required|integer|min:1',
            'min_tenure_months'     => 'required|integer|min:1',
            'min_service_months'    => 'nullable|integer|min:0',
            'color'                 => 'nullable|string|max:7',
            'description'           => 'nullable|string',
            'requires_guarantor'    => 'boolean',
            'auto_deduct_from_payroll' => 'boolean',
        ]);

        LoanType::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Loan type created.');
    }

    /* ================================================================
     | HELPERS
     |================================================================*/
    protected function generateRepaymentSchedule(Loan $loan): void
    {
        $balance     = (float) $loan->principal_amount;
        $monthlyRate = (float) $loan->interest_rate / 100 / 12;
        $emi         = (float) $loan->emi_amount;
        $date        = Carbon::parse($loan->first_emi_date);

        for ($i = 1; $i <= $loan->tenure_months; $i++) {
            $interest  = $monthlyRate > 0 ? round($balance * $monthlyRate, 2) : 0;
            $principal = round($emi - $interest, 2);
            $balance   = max(0, round($balance - $principal, 2));

            LoanRepayment::create([
                'loan_id'              => $loan->id,
                'installment_number'   => $i,
                'due_date'             => $date->copy()->toDateString(),
                'principal_component'  => $principal,
                'interest_component'   => $interest,
                'emi_amount'           => $emi,
                'balance_after'        => $balance,
                'status'               => 'pending',
            ]);

            $date->addMonth();
        }
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $loans = Loan::with(['employee', 'loanType'])->latest()->get();

        $columns = ['Employee', 'Loan Type', 'Amount', 'Balance', 'Installment', 'Start Date', 'End Date', 'Status'];

        $rows = $loans->map(fn($l) => [
            $l->employee->full_name ?? '-',
            $l->loanType->name ?? '-',
            number_format($l->amount, 2),
            number_format($l->balance ?? $l->amount, 2),
            number_format($l->installment_amount ?? 0, 2),
            $l->start_date?->format('Y-m-d') ?? '-',
            $l->end_date?->format('Y-m-d') ?? '-',
            ucfirst($l->status),
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Loans Report', $columns, $rows, 'loans-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'loans-'.now()->format('Y-m-d').'.xlsx');
    }
}