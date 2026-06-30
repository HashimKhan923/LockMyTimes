<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Candidate;
use App\Models\Tenant\Department;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Goal;
use App\Models\Tenant\JobPosting;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\Loan;
use App\Models\Tenant\PayrollRun;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\PerformanceReview;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TrainingEnrollment;
use App\Models\Tenant\Asset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(string $tenant, Request $request)
    {
        $report = $request->get('report', 'overview');
        $from   = $request->get('from', now()->startOfYear()->toDateString());
        $to     = $request->get('to', now()->toDateString());
        $deptId = $request->get('department');

        $fromDate = Carbon::parse($from);
        $toDate   = Carbon::parse($to);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $data = match($report) {
            'headcount'   => $this->headcountReport($fromDate, $toDate, $deptId),
            'attendance'  => $this->attendanceReport($fromDate, $toDate, $deptId),
            'payroll'     => $this->payrollReport($fromDate, $toDate),
            'leave'       => $this->leaveReport($fromDate, $toDate, $deptId),
            'performance' => $this->performanceReport($fromDate, $toDate),
            'recruitment' => $this->recruitmentReport($fromDate, $toDate),
            'expenses'    => $this->expensesReport($fromDate, $toDate),
            'training'    => $this->trainingReport($fromDate, $toDate),
            default       => $this->overviewReport($fromDate, $toDate),
        };

        return view('admin.reports.index', array_merge($data, compact(
            'report', 'from', 'to', 'deptId', 'departments', 'tenant'
        )));
    }

    /* ================================================================
     | OVERVIEW — executive summary
     |================================================================*/
    private function overviewReport(Carbon $from, Carbon $to): array
    {
        // Headcount
        $totalEmployees  = Employee::where('employment_status', 'active')->count();
        $newHires        = Employee::whereBetween('hire_date', [$from, $to])->count();
        $terminated      = Employee::whereBetween('termination_date', [$from, $to])->count();

        // Headcount by department
        $byDepartment = Employee::where('employment_status', 'active')
            ->with('department')
            ->get()
            ->groupBy('department_id')
            ->map(fn($g) => [
                'name'  => $g->first()->department?->name ?? 'No Department',
                'count' => $g->count(),
            ])->values()->sortByDesc('count')->take(8)->values();

        // Payroll YTD
        $payrollYTD = PayrollRun::where('status', 'paid')
            ->whereBetween('pay_date', [$from, $to])
            ->sum('total_net');

        // Monthly payroll trend
        $payrollTrend = collect(range(0, 11))->map(function ($i) use ($from) {
            $month = $from->copy()->addMonths($i);
            return [
                'label'  => $month->format('M'),
                'amount' => PayrollRun::where('status', 'paid')
                    ->whereMonth('pay_date', $month->month)
                    ->whereYear('pay_date', $month->year)
                    ->sum('total_net'),
            ];
        })->filter(fn($m) => Carbon::parse($m['label'].' 1')->lte(now()));

        // Attendance rate
        $totalDays    = Attendance::whereBetween('work_date', [$from, $to])->count();
        $presentDays  = Attendance::whereBetween('work_date', [$from, $to])->where('status', 'present')->count();
        $attendanceRate = $totalDays > 0 ? round($presentDays / $totalDays * 100, 1) : 0;

        // Leave summary
        $leavesThisPeriod = LeaveRequest::where('status', 'approved')
            ->whereBetween('start_date', [$from, $to])->count();

        // Expenses
        $expensesTotal = Expense::where('status', 'approved')
            ->whereBetween('expense_date', [$from, $to])->sum('amount');

        // Projects
        $activeProjects   = Project::where('status', 'active')->count();
        $completedProjects= Project::where('status', 'completed')
            ->whereBetween('updated_at', [$from, $to])->count();

        // Open jobs
        $openJobs         = JobPosting::where('status', 'published')->count();
        $hiredCandidates  = Candidate::where('stage', 'hired')
            ->whereBetween('updated_at', [$from, $to])->count();

        return compact(
            'totalEmployees','newHires','terminated',
            'byDepartment','payrollYTD','payrollTrend',
            'attendanceRate','leavesThisPeriod',
            'expensesTotal','activeProjects','completedProjects',
            'openJobs','hiredCandidates'
        );
    }

    /* ================================================================
     | HEADCOUNT REPORT
     |================================================================*/
    private function headcountReport(Carbon $from, Carbon $to, ?string $deptId): array
    {
        $query = Employee::with('department','position','location');
        if ($deptId) $query->where('department_id', $deptId);

        $employees = $query->orderBy('first_name')->get();

        $byStatus = $employees->groupBy('employment_status')
            ->map(fn($g) => $g->count());

        $byType = $employees->groupBy('employment_type')
            ->map(fn($g) => $g->count());

        $byGender = $employees->groupBy('gender')
            ->map(fn($g) => $g->count());

        $newHires = Employee::whereBetween('hire_date', [$from, $to])
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->with('department','position')
            ->orderByDesc('hire_date')->get();

        $terminated = Employee::whereBetween('termination_date', [$from, $to])
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->with('department')
            ->orderByDesc('termination_date')->get();

        // Tenure breakdown
        $tenureGroups = $employees->groupBy(function ($emp) {
            $months = $emp->service_months;
            if ($months < 6)   return '< 6 months';
            if ($months < 12)  return '6–12 months';
            if ($months < 36)  return '1–3 years';
            if ($months < 60)  return '3–5 years';
            return '5+ years';
        })->map(fn($g) => $g->count());

        return compact('employees','byStatus','byType','byGender','newHires','terminated','tenureGroups');
    }

    /* ================================================================
     | ATTENDANCE REPORT
     |================================================================*/
    private function attendanceReport(Carbon $from, Carbon $to, ?string $deptId): array
    {
        $records = Attendance::with('employee.department')
            ->whereBetween('work_date', [$from, $to])
            ->when($deptId, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $deptId)))
            ->get();

        $summary = [
            'present'  => $records->where('status','present')->count(),
            'absent'   => $records->where('status','absent')->count(),
            'late'     => $records->where('is_late', true)->count(),
            'overtime' => $records->sum('overtime_hours'),
        ];

        $total = $records->count();
        $summary['rate'] = $total > 0 ? round($summary['present'] / $total * 100, 1) : 0;

        // Daily trend (last 30 days)
        $dailyTrend = collect();
        $cursor = $to->copy()->subDays(29);
        while ($cursor->lte($to)) {
            $day = $records->where('work_date', $cursor->toDateString());
            $dailyTrend->push([
                'date'    => $cursor->format('M j'),
                'present' => $day->where('status','present')->count(),
                'absent'  => $day->where('status','absent')->count(),
            ]);
            $cursor->addDay();
        }

        // Per employee summary
        $perEmployee = $records->groupBy('employee_id')->map(function ($recs) {
            $emp     = $recs->first()->employee;
            $total   = $recs->count();
            $present = $recs->where('status','present')->count();
            return [
                'name'       => $emp?->full_name ?? '—',
                'department' => $emp?->department?->name ?? '—',
                'present'    => $present,
                'absent'     => $recs->where('status','absent')->count(),
                'late'       => $recs->where('is_late',true)->count(),
                'overtime'   => round($recs->sum('overtime_hours'), 1),
                'rate'       => $total > 0 ? round($present/$total*100) : 0,
            ];
        })->sortByDesc('rate')->values();

        return compact('summary','dailyTrend','perEmployee');
    }

    /* ================================================================
     | PAYROLL REPORT
     |================================================================*/
    private function payrollReport(Carbon $from, Carbon $to): array
    {
        $runs = PayrollRun::whereBetween('pay_date', [$from, $to])
            ->orderBy('pay_date')->get();

        $totals = [
            'gross'      => $runs->sum('total_gross'),
            'net'        => $runs->sum('total_net'),
            'deductions' => $runs->sum('total_deductions'),
            'taxes'      => $runs->sum('total_taxes'),
            'runs'       => $runs->count(),
        ];

        // Monthly breakdown
        $monthly = $runs->groupBy(fn($r) => $r->pay_date->format('Y-m'))
            ->map(fn($g) => [
                'label'  => $g->first()->pay_date->format('M Y'),
                'gross'  => $g->sum('total_gross'),
                'net'    => $g->sum('total_net'),
                'count'  => $g->sum('total_employees'),
            ])->values();

        // By department (from payslips)
        $byDept = Payslip::with('employee.department')
            ->whereBetween('pay_date', [$from, $to])
            ->where('status','paid')
            ->get()
            ->groupBy(fn($p) => $p->employee?->department?->name ?? 'No Department')
            ->map(fn($g) => [
                'dept'    => $g->first()->employee?->department?->name ?? 'No Department',
                'total'   => round($g->sum('net_pay'), 2),
                'count'   => $g->count(),
                'avg'     => round($g->avg('net_pay'), 2),
            ])->sortByDesc('total')->values();

        return compact('runs','totals','monthly','byDept');
    }

    /* ================================================================
     | LEAVE REPORT
     |================================================================*/
    private function leaveReport(Carbon $from, Carbon $to, ?string $deptId): array
    {
        $query = LeaveRequest::with(['employee.department','leaveType'])
            ->where('status','approved')
            ->whereBetween('start_date', [$from, $to]);

        if ($deptId) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $deptId));
        }

        $requests = $query->get();

        $summary = [
            'total'       => $requests->count(),
            'total_days'  => $requests->sum('total_days'),
            'by_type'     => $requests->groupBy('leave_type_id')
                ->map(fn($g) => [
                    'name'  => $g->first()->leaveType?->name ?? '—',
                    'count' => $g->count(),
                    'days'  => $g->sum('total_days'),
                    'color' => $g->first()->leaveType?->color ?? '#6C7DF7',
                ])->sortByDesc('days')->values(),
        ];

        // Monthly trend
        $monthly = collect(range(0, 11))->map(function ($i) use ($from, $deptId) {
            $month = $from->copy()->addMonths($i);
            $q = LeaveRequest::where('status','approved')
                ->whereMonth('start_date', $month->month)
                ->whereYear('start_date', $month->year);
            if ($deptId) $q->whereHas('employee', fn($q2) => $q2->where('department_id', $deptId));
            return [
                'label' => $month->format('M'),
                'count' => $q->count(),
                'days'  => $q->sum('total_days'),
            ];
        })->filter(fn($m) => Carbon::createFromFormat('M', $m['label'])->lte(now()));

        // Top leave takers
        $topTakers = $requests->groupBy('employee_id')
            ->map(fn($g) => [
                'name'  => $g->first()->employee?->full_name ?? '—',
                'dept'  => $g->first()->employee?->department?->name ?? '—',
                'count' => $g->count(),
                'days'  => $g->sum('total_days'),
            ])->sortByDesc('days')->take(10)->values();

        return compact('summary','monthly','topTakers','requests');
    }

    /* ================================================================
     | PERFORMANCE REPORT
     |================================================================*/
    private function performanceReport(Carbon $from, Carbon $to): array
    {
        $reviews = PerformanceReview::with(['employee.department','cycle'])
            ->where('status','completed')
            ->whereBetween('submitted_at', [$from, $to])
            ->get();

        $avgRating = round($reviews->whereNotNull('overall_rating')->avg('overall_rating'), 2);

        $ratingDist = collect([1,2,3,4,5])->mapWithKeys(fn($r) => [
            $r => $reviews->filter(fn($rev) =>
                $rev->overall_rating >= $r - 0.5 && $rev->overall_rating < $r + 0.5
            )->count()
        ]);

        $byDept = $reviews->groupBy(fn($r) => $r->employee?->department?->name ?? 'No Department')
            ->map(fn($g) => [
                'dept'   => $g->first()->employee?->department?->name ?? '—',
                'count'  => $g->count(),
                'avg'    => round($g->whereNotNull('overall_rating')->avg('overall_rating'), 2),
            ])->sortByDesc('avg')->values();

        $goals = Goal::whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('status')
            ->map(fn($g) => $g->count());

        return compact('reviews','avgRating','ratingDist','byDept','goals');
    }

    /* ================================================================
     | RECRUITMENT REPORT
     |================================================================*/
    private function recruitmentReport(Carbon $from, Carbon $to): array
    {
        $jobs = JobPosting::whereBetween('created_at', [$from, $to])
            ->withCount('candidates')->get();

        $candidates = Candidate::whereBetween('created_at', [$from, $to])
            ->with('jobPosting')->get();

        $funnel = collect(['applied','screening','interview','assessment','offer','hired','rejected'])
            ->mapWithKeys(fn($s) => [
                $s => $candidates->where('stage', $s)->count()
            ]);

        $hireRate = $candidates->count() > 0
            ? round($candidates->where('stage','hired')->count() / $candidates->count() * 100, 1)
            : 0;

        $bySource = $candidates->groupBy('source')
            ->map(fn($g) => ['source'=>($g->first()->source ?? 'Unknown'), 'count'=>$g->count()])
            ->sortByDesc('count')->values();

        $timeToHire = null; // Would need hired_at timestamp

        return compact('jobs','candidates','funnel','hireRate','bySource','timeToHire');
    }

    /* ================================================================
     | EXPENSES REPORT
     |================================================================*/
    private function expensesReport(Carbon $from, Carbon $to): array
    {
        $expenses = Expense::with(['employee.department','category'])
            ->where('status','approved')
            ->whereBetween('expense_date', [$from, $to])
            ->get();

        $totals = [
            'total'    => $expenses->sum('amount'),
            'count'    => $expenses->count(),
            'avg'      => $expenses->count() > 0 ? round($expenses->avg('amount'), 2) : 0,
            'billable' => $expenses->where('is_billable', true)->sum('amount'),
        ];

        $byCategory = $expenses->groupBy('category_id')
            ->map(fn($g) => [
                'name'    => $g->first()->category?->name ?? 'Unknown',
                'total'   => round($g->sum('amount'), 2),
                'count'   => $g->count(),
                'color'   => $g->first()->category?->color ?? '#6C7DF7',
            ])->sortByDesc('total')->values();

        $byDept = $expenses->groupBy(fn($e) => $e->employee?->department?->name ?? 'No Department')
            ->map(fn($g) => [
                'dept'  => $g->first()->employee?->department?->name ?? 'No Department',
                'total' => round($g->sum('amount'), 2),
                'count' => $g->count(),
            ])->sortByDesc('total')->values();

        $monthly = collect(range(0, 11))->map(function ($i) use ($from) {
            $month = $from->copy()->addMonths($i);
            return [
                'label' => $month->format('M'),
                'total' => round(Expense::where('status','approved')
                    ->whereMonth('expense_date', $month->month)
                    ->whereYear('expense_date', $month->year)
                    ->sum('amount'), 2),
            ];
        })->filter(fn($m) => Carbon::createFromFormat('M', $m['label'])->lte(now()));

        return compact('expenses','totals','byCategory','byDept','monthly');
    }

    /* ================================================================
     | TRAINING REPORT
     |================================================================*/
    private function trainingReport(Carbon $from, Carbon $to): array
    {
        $enrollments = TrainingEnrollment::with(['training','employee.department'])
            ->whereBetween('enrolled_at', [$from, $to])
            ->get();

        $summary = [
            'total'      => $enrollments->count(),
            'completed'  => $enrollments->where('status','completed')->count(),
            'in_progress'=> $enrollments->where('status','in_progress')->count(),
            'completion_rate' => $enrollments->count() > 0
                ? round($enrollments->where('status','completed')->count() / $enrollments->count() * 100)
                : 0,
            'avg_score'  => round($enrollments->whereNotNull('score')->avg('score'), 1),
        ];

        $byTraining = $enrollments->groupBy('training_id')
            ->map(fn($g) => [
                'title'      => $g->first()->training?->title ?? '—',
                'enrolled'   => $g->count(),
                'completed'  => $g->where('status','completed')->count(),
                'avg_score'  => round($g->whereNotNull('score')->avg('score'), 1),
            ])->sortByDesc('enrolled')->values();

        $byDept = $enrollments->groupBy(fn($e) => $e->employee?->department?->name ?? 'No Department')
            ->map(fn($g) => [
                'dept'      => $g->first()->employee?->department?->name ?? '—',
                'enrolled'  => $g->count(),
                'completed' => $g->where('status','completed')->count(),
            ])->sortByDesc('enrolled')->values();

        return compact('enrollments','summary','byTraining','byDept');
    }
}