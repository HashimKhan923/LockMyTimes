<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Expense;
use App\Models\Tenant\ExpenseApproval;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAssignee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeamController extends Controller
{
    /* ================================================================
     | Authorization helper — ensure the user manages this employee
     |================================================================*/
    protected function assertManagesEmployee(int $managerEmployeeId, int $targetEmployeeId): Employee
    {
        $target = Employee::where('manager_id', $managerEmployeeId)
            ->where('id', $targetEmployeeId)
            ->first();

        abort_unless($target, 403, 'You do not manage this employee.');
        return $target;
    }

    /* ================================================================
     | Helper — get all direct report IDs for the current manager
     |================================================================*/
    protected function getReportIds(int $managerEmployeeId)
    {
        return Employee::where('manager_id', $managerEmployeeId)->pluck('id');
    }

    /* ================================================================
     | INDEX — Team dashboard
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $reportIds = $this->getReportIds($emp->id);

        if ($reportIds->isEmpty()) {
            return view('employee.team.empty', [
                'tenantSlug' => $tenant,
                'emp'        => $emp,
            ]);
        }

        // Load reports with key relationships
        $reports = Employee::with(['position', 'department', 'user'])
            ->whereIn('id', $reportIds)
            ->orderBy('first_name')
            ->get();

        // Pending approvals counters
        $pendingLeaves = LeaveRequest::whereIn('employee_id', $reportIds)
            ->where('status', 'pending')
            ->count();

        $pendingExpenses = Expense::whereIn('employee_id', $reportIds)
            ->where('status', 'submitted')
            ->count();

        // On leave today
        $onLeaveTodayIds = LeaveRequest::whereIn('employee_id', $reportIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->pluck('employee_id')
            ->unique();

        // Clocked in today
        $clockedInTodayIds = Attendance::whereIn('employee_id', $reportIds)
            ->whereDate('work_date', today())
            ->whereNotNull('clock_in_at')
            ->pluck('employee_id')
            ->unique();

        // Per-report metrics (compact summary card data)
        $reports->each(function ($r) use ($onLeaveTodayIds, $clockedInTodayIds) {
            $r->_status_today = match (true) {
                $onLeaveTodayIds->contains($r->id)    => 'on_leave',
                $clockedInTodayIds->contains($r->id)  => 'clocked_in',
                default                               => 'absent',
            };

            $r->_pending_leaves = LeaveRequest::where('employee_id', $r->id)
                ->where('status', 'pending')->count();

            $r->_pending_expenses = Expense::where('employee_id', $r->id)
                ->where('status', 'submitted')->count();

            $r->_open_tasks = Task::whereIn('id',
                    TaskAssignee::where('employee_id', $r->id)->pluck('task_id')
                )
                ->whereNotIn('status', ['done', 'cancelled'])
                ->count();
        });

        // Stats for hero
        $headcount    = $reports->count();
        $onLeaveCount = $onLeaveTodayIds->count();
        $clockedIn    = $clockedInTodayIds->count();

        // Recent activity — last 10 leave/expense submissions across the team
        $recentLeaves = LeaveRequest::with(['employee', 'leaveType'])
            ->whereIn('employee_id', $reportIds)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentExpenses = Expense::with(['employee', 'category'])
            ->whereIn('employee_id', $reportIds)
            ->whereNotIn('status', ['draft'])
            ->orderByDesc('submitted_at')
            ->limit(5)
            ->get();

        return view('employee.team.index', [
            'tenantSlug'      => $tenant,
            'emp'             => $emp,
            'reports'         => $reports,
            'pendingLeaves'   => $pendingLeaves,
            'pendingExpenses' => $pendingExpenses,
            'headcount'       => $headcount,
            'onLeaveCount'    => $onLeaveCount,
            'clockedIn'       => $clockedIn,
            'recentLeaves'    => $recentLeaves,
            'recentExpenses'  => $recentExpenses,
        ]);
    }

    /* ================================================================
     | SHOW — Single team member detail
     |================================================================*/
    public function show(string $tenant, int $employee)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $target = $this->assertManagesEmployee($emp->id, $employee);
        $target->load(['position', 'department', 'location', 'user']);

        // Leave history (last 10)
        $leaves = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $target->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Expense history (last 10)
        $expenses = Expense::with('category')
            ->where('employee_id', $target->id)
            ->whereNotIn('status', ['draft'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Open tasks
        $openTaskIds = TaskAssignee::where('employee_id', $target->id)->pluck('task_id');
        $openTasks = Task::with('project')
            ->whereIn('id', $openTaskIds)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // Attendance (last 14 days)
        $recentAttendance = Attendance::where('employee_id', $target->id)
            ->where('work_date', '>=', now()->subDays(14))
            ->orderByDesc('work_date')
            ->get();

        // Leave balances (current year)
        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('employee_id', $target->id)
            ->where('year', now()->year)
            ->get();

        // Today's status
        $isOnLeaveToday = LeaveRequest::where('employee_id', $target->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->exists();

        $todayAttendance = Attendance::where('employee_id', $target->id)
            ->whereDate('work_date', today())
            ->first();

        // Aggregate counters
        $pendingLeaves = LeaveRequest::where('employee_id', $target->id)
            ->where('status', 'pending')->count();
        $pendingExpenses = Expense::where('employee_id', $target->id)
            ->where('status', 'submitted')->count();

        return view('employee.team.show', [
            'tenantSlug'       => $tenant,
            'emp'              => $emp,
            'member'           => $target,
            'leaves'           => $leaves,
            'expenses'         => $expenses,
            'openTasks'        => $openTasks,
            'recentAttendance' => $recentAttendance,
            'leaveBalances'    => $leaveBalances,
            'isOnLeaveToday'   => $isOnLeaveToday,
            'todayAttendance'  => $todayAttendance,
            'pendingLeaves'    => $pendingLeaves,
            'pendingExpenses'  => $pendingExpenses,
        ]);
    }

    /* ================================================================
     | LEAVE APPROVALS — QUEUE
     |================================================================*/
    public function leaveApprovals(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $reportIds = $this->getReportIds($emp->id);

        if ($reportIds->isEmpty()) {
            return redirect()->route('employee.team.index', $tenant)
                ->with('error', 'You do not have any direct reports.');
        }

        $status = $request->get('status', 'pending');

        $query = LeaveRequest::with(['employee.position', 'leaveType', 'approver'])
            ->whereIn('employee_id', $reportIds);

        if ($status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($status === 'approved') {
            $query->where('status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        }
        // 'all' = no filter

        $leaves = $query->orderByRaw("FIELD(status, 'pending','approved','rejected','cancelled')")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Attach balance info for context
        $leaves->getCollection()->transform(function ($leave) {
            $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->where('year', Carbon::parse($leave->start_date)->year)
                ->first();

            if ($balance) {
                $leave->_available_balance = (float) ($balance->allocated + $balance->accrued + $balance->carried_over + $balance->adjusted - $balance->used - $balance->pending);
            } else {
                $leave->_available_balance = null;
            }

            return $leave;
        });

        $counters = LeaveRequest::whereIn('employee_id', $reportIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'pending')   as pending,
                SUM(status = 'approved')  as approved,
                SUM(status = 'rejected')  as rejected
            ")
            ->first();

        return view('employee.team.leaves', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'leaves'     => $leaves,
            'counters'   => $counters,
            'status'     => $status,
        ]);
    }

    /* ================================================================
     | LEAVE — APPROVE
     |================================================================*/
    public function approveLeave(string $tenant, Request $request, int $leave)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $leaveReq = LeaveRequest::findOrFail($leave);

        // Authorization
        $this->assertManagesEmployee($emp->id, $leaveReq->employee_id);

        if ($leaveReq->status !== 'pending') {
            return back()->with('error', 'This leave request has already been processed.');
        }

        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        // Balance check (matches admin's logic but conservative — never allow negative from manager)
        $balance = LeaveBalance::where('employee_id', $leaveReq->employee_id)
            ->where('leave_type_id', $leaveReq->leave_type_id)
            ->where('year', Carbon::parse($leaveReq->start_date)->year)
            ->first();

        if ($balance) {
            $net = (float) ($balance->allocated + $balance->accrued + $balance->carried_over + $balance->adjusted - $balance->used - $balance->pending);
            if ($net < 0) {
                return back()->with('error',
                    'Insufficient leave balance. Approving this would create a negative balance of ' . abs($net) . ' day(s). Contact HR to adjust the balance first.'
                );
            }
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $leaveReq->update([
                'status'            => 'approved',
                'approved_by'       => auth()->id(),
                'approved_at'       => now(),
                'approver_comments' => $data['note'] ?? null,
            ]);

            // Move from pending → used on the balance row
            $this->movePendingToUsed($leaveReq, $balance);

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team leave approve failed: '.$e->getMessage());
            return back()->with('error', 'Could not approve the leave. Please try again.');
        }

        return back()->with('success', "{$leaveReq->employee->full_name}'s leave approved.");
    }

    /* ================================================================
     | LEAVE — REJECT
     |================================================================*/
    public function rejectLeave(string $tenant, Request $request, int $leave)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $leaveReq = LeaveRequest::findOrFail($leave);
        $this->assertManagesEmployee($emp->id, $leaveReq->employee_id);

        if ($leaveReq->status !== 'pending') {
            return back()->with('error', 'This leave request has already been processed.');
        }

        $data = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            $leaveReq->update([
                'status'           => 'rejected',
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
                'rejection_reason' => $data['reason'],
            ]);

            // Release pending balance
            $balance = LeaveBalance::where('employee_id', $leaveReq->employee_id)
                ->where('leave_type_id', $leaveReq->leave_type_id)
                ->where('year', Carbon::parse($leaveReq->start_date)->year)
                ->first();

            if ($balance) {
                $newPending = max(0, (float) $balance->pending - (float) $leaveReq->total_days);
                $balance->update(['pending' => $newPending]);
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team leave reject failed: '.$e->getMessage());
            return back()->with('error', 'Could not reject the leave. Please try again.');
        }

        return back()->with('success', "Leave request rejected.");
    }

    /* ================================================================
     | EXPENSE APPROVALS — QUEUE
     |================================================================*/
    public function expenseApprovals(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $reportIds = $this->getReportIds($emp->id);

        if ($reportIds->isEmpty()) {
            return redirect()->route('employee.team.index', $tenant)
                ->with('error', 'You do not have any direct reports.');
        }

        $status = $request->get('status', 'submitted');

        $query = Expense::with(['employee.position', 'category'])
            ->whereIn('employee_id', $reportIds);

        if ($status === 'submitted') {
            $query->where('status', 'submitted');
        } elseif ($status === 'approved') {
            $query->where('status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        } elseif ($status === 'paid') {
            $query->where('status', 'paid');
        }
        // 'all' = everything except drafts
        if ($status === 'all') {
            $query->whereNotIn('status', ['draft']);
        }

        $expenses = $query->orderByRaw("FIELD(status, 'submitted','approved','paid','rejected','cancelled')")
            ->orderByDesc('submitted_at')
            ->paginate(15)
            ->withQueryString();

        $counters = Expense::whereIn('employee_id', $reportIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'submitted') as submitted,
                SUM(status = 'approved')  as approved,
                SUM(status = 'paid')      as paid,
                SUM(status = 'rejected')  as rejected
            ")
            ->first();

        // Total pending money
        $pendingTotal = Expense::whereIn('employee_id', $reportIds)
            ->where('status', 'submitted')
            ->sum('amount');

        return view('employee.team.expenses', [
            'tenantSlug'   => $tenant,
            'emp'          => $emp,
            'expenses'     => $expenses,
            'counters'     => $counters,
            'pendingTotal' => $pendingTotal,
            'status'       => $status,
        ]);
    }

    /* ================================================================
     | EXPENSE — APPROVE
     |================================================================*/
    public function approveExpense(string $tenant, Request $request, int $expense)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $exp = Expense::findOrFail($expense);
        $this->assertManagesEmployee($emp->id, $exp->employee_id);

        if ($exp->status !== 'submitted') {
            return back()->with('error', 'Only submitted expenses can be approved.');
        }

        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            $exp->update([
                'status'      => 'approved',
                'approved_at' => now(),
            ]);

            // Audit trail in expense_approvals
            if (Schema::connection('tenant')->hasTable('expense_approvals')) {
                ExpenseApproval::create([
                    'expense_id'     => $exp->id,
                    'approver_id'    => auth()->id(),
                    'approval_level' => 1,
                    'decision'       => 'approved',
                    'comments'       => $data['note'] ?? null,
                    'decided_at'     => now(),
                ]);
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team expense approve failed: '.$e->getMessage());
            return back()->with('error', 'Could not approve the expense. Please try again.');
        }

        return back()->with('success', "Expense {$exp->expense_number} approved.");
    }

    /* ================================================================
     | EXPENSE — REJECT
     |================================================================*/
    public function rejectExpense(string $tenant, Request $request, int $expense)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $exp = Expense::findOrFail($expense);
        $this->assertManagesEmployee($emp->id, $exp->employee_id);

        if ($exp->status !== 'submitted') {
            return back()->with('error', 'Only submitted expenses can be rejected.');
        }

        $data = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            $exp->update([
                'status'           => 'rejected',
                'rejection_reason' => $data['reason'],
            ]);

            // Audit trail
            if (Schema::connection('tenant')->hasTable('expense_approvals')) {
                ExpenseApproval::create([
                    'expense_id'     => $exp->id,
                    'approver_id'    => auth()->id(),
                    'approval_level' => 1,
                    'decision'       => 'rejected',
                    'comments'       => $data['reason'],
                    'decided_at'     => now(),
                ]);
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team expense reject failed: '.$e->getMessage());
            return back()->with('error', 'Could not reject the expense. Please try again.');
        }

        return back()->with('success', "Expense {$exp->expense_number} rejected.");
    }

    /* ================================================================
     | Helpers
     |================================================================*/
    private function movePendingToUsed(LeaveRequest $leave, ?LeaveBalance $balance): void
    {
        if (! $balance) {
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year'          => Carbon::parse($leave->start_date)->year,
                ],
                ['allocated' => 0, 'accrued' => 0, 'used' => 0, 'pending' => 0, 'carried_over' => 0, 'adjusted' => 0]
            );
        }

        $newPending = max(0, (float) $balance->pending - (float) $leave->total_days);
        $balance->update([
            'pending' => $newPending,
            'used'    => (float) $balance->used + (float) $leave->total_days,
        ]);
    }
}