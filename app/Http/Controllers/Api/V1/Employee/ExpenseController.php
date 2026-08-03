<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseCategoryResource;
use App\Http\Resources\ExpenseResource;
use App\Models\Tenant\Expense;
use App\Models\Tenant\ExpenseApproval;
use App\Models\Tenant\ExpenseCategory;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON mirror of Employee/ExpenseController — same category limit /
 * receipt-threshold validation, same models, no Blade/redirect branches.
 */
class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $year = (int) $request->get('year', now()->year);
        $status = $request->get('status');
        $catId = $request->get('category');

        $years = Expense::where('employee_id', $emp->id)
            ->selectRaw('YEAR(expense_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $expenses = Expense::with('category')
            ->where('employee_id', $emp->id)
            ->whereYear('expense_date', $year)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($catId, fn ($q, $c) => $q->where('category_id', $c))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15);

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

        $counters = Expense::where('employee_id', $emp->id)
            ->whereYear('expense_date', $year)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'draft')     as draft,
                SUM(status = 'submitted') as submitted,
                SUM(status = 'approved')  as approved,
                SUM(status = 'rejected')  as rejected,
                SUM(status = 'paid')      as paid,
                SUM(status = 'cancelled') as cancelled
            ")
            ->first();

        $byCategory = Expense::with('category')
            ->where('employee_id', $emp->id)
            ->whereYear('expense_date', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->get()
            ->groupBy('category_id')
            ->map(fn ($g) => [
                'category' => new ExpenseCategoryResource($g->first()->category),
                'total' => (float) $g->sum('amount'),
                'count' => $g->count(),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $categories = ExpenseCategory::query()
            ->when(Schema::connection('tenant')->hasColumn('expense_categories', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return response()->json([
            'year' => $year,
            'years' => $years,
            'expenses' => ExpenseResource::collection($expenses->items()),
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'total' => $expenses->total(),
            ],
            'totals' => [
                'pending_amount' => (float) $totals->pending_amount,
                'approved_amount' => (float) $totals->approved_amount,
                'paid_amount' => (float) $totals->paid_amount,
                'rejected_amount' => (float) $totals->rejected_amount,
                'reimbursable_amount' => (float) $totals->reimbursable_amount,
            ],
            'counters' => [
                'total' => (int) $counters->total,
                'draft' => (int) $counters->draft,
                'submitted' => (int) $counters->submitted,
                'approved' => (int) $counters->approved,
                'rejected' => (int) $counters->rejected,
                'paid' => (int) $counters->paid,
                'cancelled' => (int) $counters->cancelled,
            ],
            'by_category' => $byCategory,
            'categories' => ExpenseCategoryResource::collection($categories),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'merchant' => ['nullable', 'string', 'max:200'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'project_code' => ['nullable', 'string', 'max:50'],
            'is_mileage' => ['nullable', 'boolean'],
            'miles' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'mileage_rate' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'action' => ['nullable', 'in:draft,submit'],
        ]);

        $category = ExpenseCategory::find($data['category_id']);
        $isMileage = (bool) ($data['is_mileage'] ?? false);

        if ($category && Schema::connection('tenant')->hasColumn('expense_categories', 'per_expense_limit')
            && $category->per_expense_limit
            && (float) $data['amount'] > (float) $category->per_expense_limit) {
            return response()->json([
                'message' => "Amount exceeds the per-expense limit for {$category->name} (".number_format((float) $category->per_expense_limit, 2).').',
                'errors' => ['amount' => ["Amount exceeds the per-expense limit for {$category->name}."]],
            ], 422);
        }

        if ($category
            && Schema::connection('tenant')->hasColumn('expense_categories', 'requires_receipt')
            && $category->requires_receipt
            && ! $request->hasFile('receipt')) {
            $threshold = Schema::connection('tenant')->hasColumn('expense_categories', 'receipt_required_above')
                ? (float) ($category->receipt_required_above ?? 0)
                : 0;
            if ((float) $data['amount'] > $threshold) {
                $msg = "A receipt is required for {$category->name} expenses"
                    .($threshold > 0 ? ' over '.number_format($threshold, 2) : '').'.';

                return response()->json(['message' => $msg, 'errors' => ['receipt' => [$msg]]], 422);
            }
        }

        $action = $data['action'] ?? 'submit';

        DB::connection('tenant')->beginTransaction();
        try {
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('expenses/receipts', 'public');
            }

            $expense = Expense::create([
                'employee_id' => $emp->id,
                'category_id' => $data['category_id'],
                'expense_number' => $this->nextNumber(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'expense_date' => $data['expense_date'],
                'merchant' => $data['merchant'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'project_code' => $data['project_code'] ?? null,
                'receipt_path' => $receiptPath,
                'is_mileage' => $isMileage,
                'miles' => $isMileage ? ($data['miles'] ?? null) : null,
                'mileage_rate' => $isMileage ? ($data['mileage_rate'] ?? null) : null,
                'status' => $action === 'draft' ? 'draft' : 'submitted',
                'submitted_at' => $action === 'draft' ? null : now(),
            ]);

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Expense store failed (API): '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());

            return response()->json(['success' => false, 'message' => 'Could not save your expense. Please try again.'], 500);
        }

        if ($action !== 'draft') {
            try {
                app(MailService::class)->sendExpenseSubmitted($expense->load(['employee', 'category']));
            } catch (\Throwable $e) {
                \Log::error('Expense-submitted notification failed (API): '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => $action === 'draft'
                ? "Draft expense {$expense->expense_number} saved."
                : "Expense {$expense->expense_number} submitted for approval.",
            'expense' => new ExpenseResource($expense->load('category')),
        ]);
    }

    public function show(Request $request, int $expense): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $exp = Expense::with('category')->where('employee_id', $emp->id)->findOrFail($expense);

        $approvals = collect();
        if (Schema::connection('tenant')->hasTable('expense_approvals')) {
            $approvals = ExpenseApproval::with('approver')
                ->where('expense_id', $exp->id)
                ->orderBy('approval_level')
                ->orderBy('id')
                ->get();
        }

        $exp->timeline = $this->buildTimeline($exp, $approvals);
        $exp->approvals = $approvals->map(fn ($a) => [
            'level' => $a->approval_level,
            'approver_name' => $a->approver?->name,
            'decision' => $a->decision,
            'decided_at' => $a->decided_at?->toIso8601String(),
            'comments' => $a->comments,
        ])->values();

        return response()->json(['expense' => new ExpenseResource($exp)]);
    }

    public function submit(Request $request, int $expense): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $exp = Expense::where('employee_id', $emp->id)->findOrFail($expense);

        if ($exp->status !== 'draft') {
            return $this->fail('Only draft expenses can be submitted.');
        }

        $exp->update(['status' => 'submitted', 'submitted_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Submitted for approval.']);
    }

    public function destroy(Request $request, int $expense): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
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

        return response()->json(['success' => true, 'message' => 'Expense deleted.']);
    }

    public function receipt(Request $request, int $expense): Response
    {
        $emp = $this->employeeOrFail($request);
        $exp = Expense::where('employee_id', $emp->id)->findOrFail($expense);

        abort_if(! $exp->receipt_path, 404, 'No receipt attached.');

        $disk = Storage::disk('public');
        abort_if(! $disk->exists($exp->receipt_path), 404, 'Receipt file is missing.');

        return $disk->response($exp->receipt_path);
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

    protected function nextNumber(): string
    {
        $maxId = (int) (Expense::max('id') ?? 0);

        return 'EXP-'.now()->format('Ym').'-'.str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function buildTimeline(Expense $exp, $approvals): array
    {
        $events = [];

        $events[] = [
            'icon' => 'file-plus', 'color' => 'gray', 'title' => 'Expense created',
            'detail' => 'Draft started', 'when' => Carbon::parse($exp->created_at),
        ];

        if ($exp->submitted_at) {
            $events[] = [
                'icon' => 'send', 'color' => 'brand', 'title' => 'Submitted for approval',
                'detail' => null, 'when' => Carbon::parse($exp->submitted_at),
            ];
        }

        foreach ($approvals as $a) {
            if ($a->decision === 'approved') {
                $events[] = [
                    'icon' => 'check-circle', 'color' => 'green',
                    'title' => 'Approved by '.($a->approver->name ?? 'manager'),
                    'detail' => $a->comments ?: null, 'when' => $a->decided_at ?: $a->updated_at,
                ];
            } elseif ($a->decision === 'rejected') {
                $events[] = [
                    'icon' => 'x-circle', 'color' => 'red',
                    'title' => 'Rejected by '.($a->approver->name ?? 'manager'),
                    'detail' => $a->comments ?: null, 'when' => $a->decided_at ?: $a->updated_at,
                ];
            }
        }

        if ($exp->status === 'approved' && $exp->approved_at && $approvals->where('decision', 'approved')->isEmpty()) {
            $events[] = ['icon' => 'check-circle', 'color' => 'green', 'title' => 'Approved', 'detail' => null, 'when' => Carbon::parse($exp->approved_at)];
        }

        if ($exp->status === 'rejected' && $exp->rejection_reason && $approvals->where('decision', 'rejected')->isEmpty()) {
            $events[] = ['icon' => 'x-circle', 'color' => 'red', 'title' => 'Rejected', 'detail' => $exp->rejection_reason, 'when' => $exp->updated_at];
        }

        if ($exp->paid_at) {
            $events[] = ['icon' => 'banknote', 'color' => 'green', 'title' => 'Reimbursed', 'detail' => 'Payment completed', 'when' => Carbon::parse($exp->paid_at)];
        }

        if ($exp->status === 'cancelled') {
            $events[] = ['icon' => 'ban', 'color' => 'gray', 'title' => 'Cancelled', 'detail' => null, 'when' => $exp->updated_at];
        }

        usort($events, fn ($a, $b) => Carbon::parse($a['when'])->timestamp <=> Carbon::parse($b['when'])->timestamp);

        foreach ($events as &$e) {
            $e['when'] = Carbon::parse($e['when'])->toIso8601String();
        }

        return $events;
    }
}
