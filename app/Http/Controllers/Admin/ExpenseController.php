<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Expense;
use App\Models\Tenant\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(string $tenant, Request $request)
    {
        $status = $request->get('status', 'submitted');

        $query = Expense::with(['employee.department', 'category'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($empId = $request->get('employee')) {
            $query->where('employee_id', $empId);
        }

        if ($catId = $request->get('category')) {
            $query->where('category_id', $catId);
        }

        if ($from = $request->get('from')) {
            $query->where('expense_date', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->where('expense_date', '<=', $to);
        }

        $expenses   = $query->paginate(20)->withQueryString();
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $employees  = Employee::active()->orderBy('first_name')->get();

        $stats = [
            'pending'        => Expense::where('status', 'submitted')->count(),
            'approved_month' => Expense::where('status', 'approved')
                                ->whereMonth('expense_date', now()->month)->sum('amount'),
            'rejected_month' => Expense::where('status', 'rejected')
                                ->whereMonth('expense_date', now()->month)->count(),
            'total_ytd'      => Expense::where('status', 'approved')
                                ->whereYear('expense_date', now()->year)->sum('amount'),
        ];

        // Monthly chart
        $chart = collect(range(5, 0))->map(function ($i) {
            $month = now()->subMonths($i);
            return [
                'label'  => $month->format('M'),
                'amount' => Expense::where('status', 'approved')
                    ->whereMonth('expense_date', $month->month)
                    ->whereYear('expense_date', $month->year)
                    ->sum('amount'),
            ];
        });

        // Category breakdown — use collection groupBy to avoid raw column issues
        $categoryBreakdown = Expense::with('category')
            ->where('status', 'approved')
            ->whereYear('expense_date', now()->year)
            ->get()
            ->groupBy('category_id')
            ->map(fn($group) => (object)[
                'total'    => $group->sum('amount'),
                'category' => $group->first()->category,
            ])
            ->sortByDesc('total')
            ->take(6);

        return view('admin.expenses.index', compact(
            'expenses', 'categories', 'employees',
            'stats', 'chart', 'categoryBreakdown',
            'status', 'tenant'
        ));
    }

    public function approve(string $tenant, Request $request, Expense $expense)
    {
        if ($expense->status !== 'submitted') {
            return back()->with('error', 'Only submitted expenses can be approved.');
        }

        $expense->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', "Expense approved — \${$expense->amount}.");
    }

    public function reject(string $tenant, Request $request, Expense $expense)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($expense->status !== 'submitted') {
            return back()->with('error', 'Only submitted expenses can be rejected.');
        }

        $expense->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', 'Expense rejected.');
    }

    public function markPaid(string $tenant, Expense $expense)
    {
        if ($expense->status !== 'approved') {
            return back()->with('error', 'Only approved expenses can be marked as paid.');
        }

        $expense->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Expense marked as paid.');
    }

    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'category_id'  => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'title'        => 'required|string|max:200',
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => 'nullable|string|max:3',
            'description'  => 'nullable|string|max:500',
            'merchant'     => 'nullable|string|max:200',
            'receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')
                ->store('expenses/receipts', 'public');
        }

        $data['expense_number'] = 'EXP-' . now()->format('Ym') . '-'
            . str_pad((Expense::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
        $data['status']         = 'submitted';
        $data['currency']       = $data['currency'] ?? 'USD';
        $data['submitted_at']   = now();

        Expense::create($data);

        return back()->with('success', 'Expense submitted successfully.');
    }

    public function destroy(string $tenant, Expense $expense)
    {
        if (! in_array($expense->status, ['submitted', 'draft'])) {
            return back()->with('error', 'Cannot delete a processed expense.');
        }

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();
        return back()->with('success', 'Expense deleted.');
    }

    public function categories(string $tenant)
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();
        return view('admin.expenses.categories', compact('categories', 'tenant'));
    }

    public function storeCategory(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'code'            => 'required|string|max:20|unique:expense_categories,code',
            'description'     => 'nullable|string',
            'requires_receipt'=> 'boolean',
            'max_amount'      => 'nullable|numeric|min:0',
            'color'           => 'nullable|string|max:7',
            'gl_code'         => 'nullable|string|max:50',
        ]);

        ExpenseCategory::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Category created.');
    }

    public function updateCategory(string $tenant, Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'requires_receipt'=> 'boolean',
            'max_amount'      => 'nullable|numeric|min:0',
            'color'           => 'nullable|string|max:7',
            'is_active'       => 'boolean',
        ]);

        $expenseCategory->update($data);
        return back()->with('success', 'Category updated.');
    }
}