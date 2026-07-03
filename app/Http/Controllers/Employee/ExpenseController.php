<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Expense;
use App\Models\Tenant\ExpenseApproval;
use App\Models\Tenant\ExpenseCategory;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /* ================================================================
     | INDEX — Expense list + summary
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $year   = (int) ($request->get('year', now()->year));
        $status = $request->get('status');           // draft|submitted|approved|rejected|paid|cancelled
        $catId  = $request->get('category');

        /* ───────── Available years for this employee ───────── */
        $years = Expense::where('employee_id', $emp->id)
            ->selectRaw('YEAR(expense_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        /* ───────── Expenses list ───────── */
        $expenses = Expense::with('category')
            ->where('employee_id', $emp->id)
            ->whereYear('expense_date', $year)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($catId, fn ($q, $c) => $q->where('category_id', $c))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        /* ───────── Status counters for chips ───────── */
        $counters = Expense::where('employee_id', $emp->id)
            ->whereYear('expense_date', $year)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'draft')     as d,
                SUM(status = 'submitted') as s,
                SUM(status = 'approved')  as a,
                SUM(status = 'rejected')  as r,
                SUM(status = 'paid')      as p,
                SUM(status = 'cancelled') as c
            ")
            ->first();

        /* ───────── Money totals ───────── */
        $totals = Expense::where('employee_id', $emp->id)
            ->whereYear('expense_date', $year)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status='submitted' THEN amount END),0) as pending_amount,
                COALESCE(SUM(CASE WHEN status='approved'  THEN amount END),0) as approved_amount,
                COALESCE(SUM(CASE WHEN status='paid'      THEN amount END),0) as paid_amount,
                COALESCE(SUM(CASE WHEN status='rejected'  THEN amount END),0) as rejected_amount,
                COALESCE(SUM(CASE WHEN status IN ('submitted','approved') THEN amount END),0) as reimbursable_amount
            ")
            ->first();

        /* ───────── Category breakdown (approved + paid this year) ───────── */
        $byCategory = Expense::with('category')
            ->where('employee_id', $emp->id)
            ->whereYear('expense_date', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->get()
            ->groupBy('category_id')
            ->map(fn ($g) => (object) [
                'category' => $g->first()->category,
                'total'    => (float) $g->sum('amount'),
                'count'    => $g->count(),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        /* ───────── All active categories for filter ───────── */
        $categories = ExpenseCategory::query()
            ->when(Schema::connection('tenant')->hasColumn('expense_categories', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('employee.expenses.index', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'year'       => $year,
            'status'     => $status,
            'catId'      => $catId,
            'expenses'   => $expenses,
            'counters'   => $counters,
            'totals'     => $totals,
            'byCategory' => $byCategory,
            'categories' => $categories,
            'years'      => $years,
        ]);
    }

    /* ================================================================
     | CREATE — Submit form
     |================================================================*/
    public function create(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $categories = ExpenseCategory::query()
            ->when(Schema::connection('tenant')->hasColumn('expense_categories', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        $defaultCurrency = view()->shared('tenantCurrency') ?? 'USD';

        return view('employee.expenses.create', [
            'tenantSlug'      => $tenant,
            'emp'             => $emp,
            'categories'      => $categories,
            'defaultCurrency' => $defaultCurrency,
        ]);
    }

    /* ================================================================
     | STORE — Create expense (draft or submitted)
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'category_id'    => ['required', 'integer', Rule::exists('expense_categories', 'id')],
            'title'          => ['required', 'string', 'min:3', 'max:200'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'expense_date'   => ['required', 'date', 'before_or_equal:today'],
            'merchant'       => ['nullable', 'string', 'max:200'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'project_code'   => ['nullable', 'string', 'max:50'],
            'is_mileage'     => ['nullable', 'boolean'],
            'miles'          => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'mileage_rate'   => ['nullable', 'numeric', 'min:0', 'max:99'],
            'receipt'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'action'         => ['nullable', 'in:draft,submit'],
        ]);

        $category = ExpenseCategory::find($data['category_id']);
        $isMileage = (bool) ($data['is_mileage'] ?? false);

        // Enforce per-expense limit if defined on the category
        if ($category && Schema::connection('tenant')->hasColumn('expense_categories', 'per_expense_limit')
            && $category->per_expense_limit
            && (float) $data['amount'] > (float) $category->per_expense_limit) {
            return back()->withInput()->withErrors([
                'amount' => "Amount exceeds the per-expense limit for {$category->name} ("
                          . number_format((float) $category->per_expense_limit, 2) . ").",
            ]);
        }

        // Receipt required-above-threshold check
        if ($category
            && Schema::connection('tenant')->hasColumn('expense_categories', 'requires_receipt')
            && $category->requires_receipt
            && ! $request->hasFile('receipt')) {
            $threshold = Schema::connection('tenant')->hasColumn('expense_categories', 'receipt_required_above')
                ? (float) ($category->receipt_required_above ?? 0)
                : 0;
            if ((float) $data['amount'] > $threshold) {
                return back()->withInput()->withErrors([
                    'receipt' => "A receipt is required for {$category->name} expenses"
                              . ($threshold > 0 ? " over " . number_format($threshold, 2) : '')
                              . ".",
                ]);
            }
        }

        $action = $data['action'] ?? 'submit';

        DB::connection('tenant')->beginTransaction();
        try {
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('expenses/receipts', 'public');
            }

            $payload = [
                'employee_id'    => $emp->id,
                'category_id'    => $data['category_id'],
                'expense_number' => $this->nextNumber(),
                'title'          => $data['title'],
                'description'    => $data['description'] ?? null,
                'amount'         => $data['amount'],
                'currency'       => strtoupper($data['currency'] ?? view()->shared('tenantCurrency') ?? 'USD'),
                'expense_date'   => $data['expense_date'],
                'merchant'       => $data['merchant'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'project_code'   => $data['project_code'] ?? null,
                'receipt_path'   => $receiptPath,
                'is_mileage'     => $isMileage,
                'miles'          => $isMileage ? ($data['miles'] ?? null) : null,
                'mileage_rate'   => $isMileage ? ($data['mileage_rate'] ?? null) : null,
                'status'         => $action === 'draft' ? 'draft' : 'submitted',
                'submitted_at'   => $action === 'draft' ? null : now(),
            ];

            $expense = Expense::create($payload);

            DB::connection('tenant')->commit();

            if ($action !== 'draft') {
                app(MailService::class)->sendExpenseSubmitted($expense->load(['employee', 'category']));
            }
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Expense store failed: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return back()->withInput()->with('error', 'Could not save your expense. Please try again.');
        }

        $msg = $action === 'draft'
            ? "Draft expense {$expense->expense_number} saved."
            : "Expense {$expense->expense_number} submitted for approval.";

        return redirect()
            ->route('employee.expenses.show', [$tenant, $expense->id])
            ->with('success', $msg);
    }

    /* ================================================================
     | SHOW — Expense detail
     |================================================================*/
    public function show(string $tenant, int $expense)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $exp = Expense::with('category')
            ->where('employee_id', $emp->id)
            ->findOrFail($expense);

        // Pull approval history (separate query — table might be empty)
        $approvals = collect();
        if (Schema::connection('tenant')->hasTable('expense_approvals')) {
            $approvals = ExpenseApproval::with('approver')
                ->where('expense_id', $exp->id)
                ->orderBy('approval_level')
                ->orderBy('id')
                ->get();
        }

        // Build status timeline
        $timeline = $this->buildTimeline($exp, $approvals);

        return view('employee.expenses.show', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'expense'    => $exp,
            'approvals'  => $approvals,
            'timeline'   => $timeline,
        ]);
    }

    /* ================================================================
     | SUBMIT — Move draft → submitted
     |================================================================*/
    public function submit(string $tenant, int $expense)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $exp = Expense::where('employee_id', $emp->id)->findOrFail($expense);

        if ($exp->status !== 'draft') {
            return $this->fail('Only draft expenses can be submitted.');
        }

        $exp->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Submitted for approval.']);
        }

        return back()->with('success', "Expense {$exp->expense_number} submitted for approval.");
    }

    /* ================================================================
     | DESTROY — Delete draft or cancel submitted (own only)
     |================================================================*/
    public function destroy(string $tenant, int $expense, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $exp = Expense::where('employee_id', $emp->id)->findOrFail($expense);

        if (! in_array($exp->status, ['draft', 'submitted'])) {
            return $this->fail('You can only delete draft or submitted expenses.');
        }

        DB::connection('tenant')->beginTransaction();
        try {
            if ($exp->receipt_path) {
                Storage::disk('public')->delete($exp->receipt_path);
            }
            $exp->delete();
            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            return $this->fail('Could not delete. Please try again.');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Expense deleted.']);
        }

        return redirect()
            ->route('employee.expenses.index', $tenant)
            ->with('success', "Expense {$exp->expense_number} deleted.");
    }

    /* ================================================================
     | RECEIPT — Secure download (own only)
     |================================================================*/
    public function receipt(string $tenant, int $expense)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $exp = Expense::where('employee_id', $emp->id)->findOrFail($expense);

        if (! $exp->receipt_path) {
            abort(404, 'No receipt attached.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($exp->receipt_path)) {
            abort(404, 'Receipt file is missing.');
        }

        return $disk->response($exp->receipt_path);
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    protected function nextNumber(): string
    {
        $maxId = (int) (Expense::max('id') ?? 0);
        return 'EXP-' . now()->format('Ym') . '-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function fail(string $msg, int $status = 422)
    {
        if (request()->wantsJson()) {
            return response()->json(['success' => false, 'message' => $msg], $status);
        }
        return back()->with('error', $msg);
    }

    protected function buildTimeline(Expense $exp, $approvals): array
    {
        $events = [];

        // Created
        $events[] = [
            'icon'   => 'file-plus',
            'color'  => 'gray',
            'title'  => 'Expense created',
            'detail' => 'Draft started',
            'when'   => Carbon::parse($exp->created_at),
        ];

        // Submitted
        if ($exp->submitted_at) {
            $events[] = [
                'icon'   => 'send',
                'color'  => 'brand',
                'title'  => 'Submitted for approval',
                'detail' => null,
                'when'   => Carbon::parse($exp->submitted_at),
            ];
        }

        // Approval events from the approvals table
        foreach ($approvals as $a) {
            if ($a->decision === 'approved') {
                $events[] = [
                    'icon'   => 'check-circle',
                    'color'  => 'green',
                    'title'  => 'Approved by '.($a->approver->name ?? 'manager'),
                    'detail' => $a->comments ?: null,
                    'when'   => $a->decided_at ?: $a->updated_at,
                ];
            } elseif ($a->decision === 'rejected') {
                $events[] = [
                    'icon'   => 'x-circle',
                    'color'  => 'red',
                    'title'  => 'Rejected by '.($a->approver->name ?? 'manager'),
                    'detail' => $a->comments ?: null,
                    'when'   => $a->decided_at ?: $a->updated_at,
                ];
            }
        }

        // Approved (column-level) — fallback if no approval rows
        if ($exp->status === 'approved' && $exp->approved_at && $approvals->where('decision', 'approved')->isEmpty()) {
            $events[] = [
                'icon'   => 'check-circle',
                'color'  => 'green',
                'title'  => 'Approved',
                'detail' => null,
                'when'   => Carbon::parse($exp->approved_at),
            ];
        }

        // Rejected (column-level) — fallback
        if ($exp->status === 'rejected' && $exp->rejection_reason && $approvals->where('decision', 'rejected')->isEmpty()) {
            $events[] = [
                'icon'   => 'x-circle',
                'color'  => 'red',
                'title'  => 'Rejected',
                'detail' => $exp->rejection_reason,
                'when'   => $exp->updated_at,
            ];
        }

        // Paid
        if ($exp->paid_at) {
            $events[] = [
                'icon'   => 'banknote',
                'color'  => 'green',
                'title'  => 'Reimbursed',
                'detail' => 'Payment completed',
                'when'   => Carbon::parse($exp->paid_at),
            ];
        }

        // Cancelled
        if ($exp->status === 'cancelled') {
            $events[] = [
                'icon'   => 'ban',
                'color'  => 'gray',
                'title'  => 'Cancelled',
                'detail' => null,
                'when'   => $exp->updated_at,
            ];
        }

        // Sort ascending by time
        usort($events, fn ($a, $b) => Carbon::parse($a['when'])->timestamp <=> Carbon::parse($b['when'])->timestamp);

        return $events;
    }
}