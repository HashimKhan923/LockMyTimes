<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\PayrollRun;
use App\Models\Tenant\SalaryComponent;
use App\Services\ExportService;
use App\Services\NotificationService;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(protected PayrollService $payrollService) {}

    /* ================================================================
     | INDEX — payroll dashboard
     |================================================================*/
    public function index(string $tenant)
    {
        $runs = PayrollRun::latest()->paginate(12);

        $stats = [
            'total_runs'        => PayrollRun::count(),
            'last_run'          => PayrollRun::latest()->first(),
            'total_paid_ytd'    => PayrollRun::where('status', 'paid')
                                    ->whereYear('pay_date', now()->year)
                                    ->sum('total_net'),
            'pending_approval'  => PayrollRun::where('status', 'pending_approval')->count(),
        ];

        // Monthly payroll chart (last 6 months)
        $chart = collect(range(5, 0))->map(function ($i) {
            $month = now()->subMonths($i);
            return [
                'label'  => $month->format('M'),
                'amount' => PayrollRun::where('status', 'paid')
                    ->whereMonth('pay_date', $month->month)
                    ->whereYear('pay_date', $month->year)
                    ->sum('total_net'),
            ];
        });

        return view('admin.payroll.index', compact('runs', 'stats', 'chart', 'tenant'));
    }

    /* ================================================================
     | CREATE RUN
     |================================================================*/
public function createRun(string $tenant, Request $request)
{
    $data = $request->validate([
        'period_start' => 'required|date',
        'period_end'   => 'required|date|after_or_equal:period_start',
        'pay_date'     => 'required|date',
        'frequency'    => 'required|in:monthly,bi_weekly,semi_monthly,weekly',
        'notes'        => 'nullable|string',
    ]);

    $exists = PayrollRun::where('period_start', $data['period_start'])
        ->where('period_end', $data['period_end'])
        ->exists();

    if ($exists) {
        return back()->with('error', 'A payroll run already exists for this period.');
    }

    $run = PayrollRun::create([
        'run_number'   => 'PR-' . now()->format('Ym') . '-' . str_pad(PayrollRun::count() + 1, 3, '0', STR_PAD_LEFT),
        'pay_schedule' => $data['frequency'],   // ← map to correct column
        'period_start' => $data['period_start'],
        'period_end'   => $data['period_end'],
        'pay_date'     => $data['pay_date'],
        'notes'        => $data['notes'] ?? null,
        'status'       => 'draft',
        'created_by'   => auth()->id(),
    ]);

    $this->payrollService->generateRun($run);

    NotificationService::payrollReady(auth()->user(),
        $run->period_start . ' – ' . $run->period_end,
        route('admin.payroll.show', [$tenant, $run->id])
    );

    return redirect()
        ->route('admin.payroll.show', [$tenant, $run->id])
        ->with('success', "Payroll run {$run->run_number} created with {$run->total_employees} payslips.");
}

    /* ================================================================
     | SHOW RUN — payroll run detail with all payslips
     |================================================================*/
    public function show(string $tenant, PayrollRun $payrollRun)
    {
        $payrollRun->load(['payslips.employee.department']);

        $deptBreakdown = $payrollRun->payslips
            ->groupBy(fn($p) => $p->employee->department?->name ?? 'No Department')
            ->map(fn($group) => [
                'count'     => $group->count(),
                'net_total' => $group->sum('net_salary'),
            ]);

        return view('admin.payroll.show',
            compact('payrollRun', 'deptBreakdown', 'tenant'));
    }

    /* ================================================================
     | APPROVE RUN
     |================================================================*/
    public function approve(string $tenant, PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== 'draft') {
            return back()->with('error', 'Only draft payroll runs can be approved.');
        }

        $payrollRun->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Mark all payslips as approved
        $payrollRun->payslips()->update(['status' => 'finalized']);

        NotificationService::payrollApproved(auth()->user(),
            $payrollRun->period_start . ' – ' . $payrollRun->period_end,
            route('admin.payroll.show', [$tenant, $payrollRun->id])
        );

        return back()->with('success', "Payroll run {$payrollRun->run_number} approved.");
    }

    /* ================================================================
     | MARK AS PAID
     |================================================================*/
    public function markPaid(string $tenant, PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== 'approved') {
            return back()->with('error', 'Only approved payroll runs can be marked as paid.');
        }

        $payrollRun->update(['status' => 'paid']);  // remove paid_at — not in migration
        $payrollRun->payslips()->update(['status' => 'paid']);

        return back()->with('success', "Payroll marked as paid.");
    }

    /* ================================================================
     | REGENERATE — recalculate all payslips in a run
     |================================================================*/
    public function regenerate(string $tenant, PayrollRun $payrollRun)
    {
        if (! in_array($payrollRun->status, ['draft'])) {
            return back()->with('error', 'Only draft runs can be regenerated.');
        }

        $this->payrollService->generateRun($payrollRun);

        return back()->with('success', 'Payroll recalculated successfully.');
    }

    /* ================================================================
     | PAYSLIP VIEW
     |================================================================*/
    public function payslip(string $tenant, Payslip $payslip)
    {
        $payslip->load(['employee.department', 'employee.position', 'items', 'payrollRun']);
        return view('admin.payroll.payslip', compact('payslip', 'tenant'));
    }

    /* ================================================================
     | SALARY COMPONENTS
     |================================================================*/
    public function components(string $tenant)
    {
        $components = SalaryComponent::withCount('employeeSalaryComponents')->orderBy('type')->get();
        return view('admin.payroll.components', compact('components', 'tenant'));
    }

    public function storeComponent(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'code'          => 'required|string|max:20|unique:salary_components,code',
            'type'          => 'required|in:earning,deduction,tax,benefit',
            'calculation'   => 'required|in:fixed,percentage,formula',
            'default_amount'=> 'nullable|numeric|min:0',
            'is_taxable'    => 'boolean',
            'is_mandatory'  => 'boolean',
            'description'   => 'nullable|string',
        ]);

        SalaryComponent::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Salary component created.');
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $runs = PayrollRun::latest()->get();

        $columns = ['Period', 'Pay Date', 'Frequency', 'Employees', 'Gross Pay', 'Deductions', 'Net Pay', 'Status'];

        $rows = $runs->map(fn($r) => [
            $r->period_start->format('M d') . ' – ' . $r->period_end->format('M d, Y'),
            $r->pay_date->format('Y-m-d'),
            ucfirst(str_replace('_', ' ', $r->frequency)),
            $r->total_employees ?? '-',
            number_format($r->total_gross, 2),
            number_format($r->total_deductions, 2),
            number_format($r->total_net, 2),
            ucfirst(str_replace('_', ' ', $r->status)),
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Payroll Runs Report', $columns, $rows, 'payroll-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'payroll-'.now()->format('Y-m-d').'.xlsx');
    }
}