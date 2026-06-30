<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\Kudo;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\Loan;
use App\Models\Tenant\PayrollRun;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(string $tenant)
    {
        $today = Carbon::today();

        /* ====== Employee Stats ====== */
        $totalEmployees    = Employee::active()->count();
        $newThisMonth      = Employee::active()
            ->whereMonth('hire_date', $today->month)
            ->whereYear('hire_date', $today->year)->count();

        /* ====== Attendance Today ====== */
        $presentToday = Attendance::where('work_date', $today->toDateString())
            ->where('status', 'present')->count();

        $onLeaveToday = Attendance::where('work_date', $today->toDateString())
            ->where('status', 'on_leave')->count();

        $lateToday = Attendance::where('work_date', $today->toDateString())
            ->where('is_late', true)->count();

        $attendanceRate = $totalEmployees > 0
            ? round(($presentToday / $totalEmployees) * 100)
            : 0;

        /* ====== Attendance last 7 days (for chart) ====== */
        $attendanceChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date  = Carbon::today()->subDays($daysAgo);
            $total = Attendance::where('work_date', $date->toDateString())->count();
            $present = Attendance::where('work_date', $date->toDateString())
                ->where('status', 'present')->count();
            return [
                'label'   => $date->format('D'),
                'date'    => $date->toDateString(),
                'present' => $present,
                'total'   => $total,
                'rate'    => $total > 0 ? round(($present / $total) * 100) : 0,
            ];
        });

        /* ====== Pending Approvals ====== */
        $pendingLeaves   = LeaveRequest::where('status', 'pending')->count();
        $pendingExpenses = Expense::where('status', 'submitted')->count();
        $pendingLoans    = Loan::where('status', 'pending')->count();
        $totalPending    = $pendingLeaves + $pendingExpenses + $pendingLoans;

        /* ====== Payroll ====== */
        $lastPayrollRun   = PayrollRun::latest()->first();
        $nextPayrollDraft = PayrollRun::where('status', 'draft')->latest()->first();

        /* ====== Projects & Tasks ====== */
        $activeProjects     = Project::where('status', 'active')->count();
        $overdueTasks       = Task::overdue()->count();
        $tasksInProgress    = Task::where('status', 'in_progress')->count();

        /* ====== Recent Activity ====== */
        $recentAttendances = Attendance::with('employee')
            ->where('work_date', $today->toDateString())
            ->orderBy('clock_in_at', 'desc')
            ->take(8)
            ->get();

        $recentLeaves = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        /* ====== Today's Birthdays & Anniversaries ====== */
        $birthdaysToday = Employee::active()
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->get();

        $anniversariesToday = Employee::active()
            ->whereMonth('hire_date', $today->month)
            ->whereDay('hire_date', $today->day)
            ->where('hire_date', '<', $today->toDateString())
            ->get();

        /* ====== Upcoming Holidays ====== */
        $upcomingHolidays = Holiday::where('date', '>=', $today->toDateString())
            ->where('date', '<=', $today->addDays(30)->toDateString())
            ->where('is_active', true)
            ->orderBy('date')
            ->take(3)
            ->get();

        /* ====== Kudos Wall ====== */
        $recentKudos = Kudo::with(['fromEmployee', 'toEmployee'])
            ->where('is_public', true)
            ->latest()
            ->take(4)
            ->get();

        /* ====== Department headcount (for chart) ====== */
        $deptChart = Employee::active()
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->select('departments.name', 'departments.color', DB::raw('count(*) as total'))
            ->groupBy('departments.id', 'departments.name', 'departments.color')
            ->orderByDesc('total')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalEmployees', 'newThisMonth',
            'presentToday', 'onLeaveToday', 'lateToday', 'attendanceRate',
            'attendanceChart',
            'pendingLeaves', 'pendingExpenses', 'pendingLoans', 'totalPending',
            'lastPayrollRun', 'nextPayrollDraft',
            'activeProjects', 'overdueTasks', 'tasksInProgress',
            'recentAttendances', 'recentLeaves',
            'birthdaysToday', 'anniversariesToday',
            'upcomingHolidays', 'recentKudos', 'deptChart',
            'today'
        ));
    }
}