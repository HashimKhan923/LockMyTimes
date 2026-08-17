<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TeamMemberResource;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Expense;
use App\Models\Tenant\ExpenseApproval;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAssignee;
use App\Models\Tenant\User;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\TenantManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * JSON mirror of Employee/TeamController — manager-mode approvals for
 * direct reports. Same manages-employee authorization, same balance
 * increment/decrement logic on approve/reject.
 */
class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $reportIds = $this->getReportIds($emp->id);

        if ($reportIds->isEmpty()) {
            return response()->json(['is_manager' => false]);
        }

        $reports = Employee::with(['position', 'department'])
            ->whereIn('id', $reportIds)
            ->orderBy('first_name')
            ->get();

        $pendingLeaves = LeaveRequest::whereIn('employee_id', $reportIds)->where('status', 'pending')->count();
        $pendingExpenses = Expense::whereIn('employee_id', $reportIds)->where('status', 'submitted')->count();

        $onLeaveTodayIds = LeaveRequest::whereIn('employee_id', $reportIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->pluck('employee_id')
            ->unique();

        $clockedInTodayIds = Attendance::whereIn('employee_id', $reportIds)
            ->whereDate('work_date', today())
            ->whereNotNull('clock_in_at')
            ->pluck('employee_id')
            ->unique();

        $reports->each(function ($r) use ($onLeaveTodayIds, $clockedInTodayIds) {
            $r->_status_today = match (true) {
                $onLeaveTodayIds->contains($r->id) => 'on_leave',
                $clockedInTodayIds->contains($r->id) => 'clocked_in',
                default => 'absent',
            };
            $r->_pending_leaves = LeaveRequest::where('employee_id', $r->id)->where('status', 'pending')->count();
            $r->_pending_expenses = Expense::where('employee_id', $r->id)->where('status', 'submitted')->count();
            $r->_open_tasks = Task::whereIn('id', TaskAssignee::where('employee_id', $r->id)->pluck('task_id'))
                ->whereNotIn('status', ['done', 'cancelled'])
                ->count();
        });

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

        return response()->json([
            'is_manager' => true,
            'reports' => TeamMemberResource::collection($reports),
            'pending_leaves' => $pendingLeaves,
            'pending_expenses' => $pendingExpenses,
            'headcount' => $reports->count(),
            'on_leave_count' => $onLeaveTodayIds->count(),
            'clocked_in' => $clockedInTodayIds->count(),
            'recent_leaves' => LeaveRequestResource::collection($recentLeaves),
            'recent_expenses' => ExpenseResource::collection($recentExpenses),
        ]);
    }

    public function show(Request $request, int $employee): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $target = $this->assertManagesEmployee($emp->id, $employee);
        $target->load(['position', 'department', 'location']);

        $leaves = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $target->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $expenses = Expense::with('category')
            ->where('employee_id', $target->id)
            ->whereNotIn('status', ['draft'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $openTaskIds = TaskAssignee::where('employee_id', $target->id)->pluck('task_id');
        $openTasks = Task::with('project')
            ->whereIn('id', $openTaskIds)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $recentAttendance = Attendance::where('employee_id', $target->id)
            ->where('work_date', '>=', now()->subDays(14))
            ->orderByDesc('work_date')
            ->get();

        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('employee_id', $target->id)
            ->where('year', now()->year)
            ->get()
            ->filter(fn ($b) => $b->leaveType)
            ->map(function ($b) {
                $total = (float) ($b->allocated + $b->accrued + $b->carried_over + $b->adjusted);
                $available = $b->available;

                return [
                    'name' => $b->leaveType->name,
                    'color' => $b->leaveType->color ?? '#6C7DF7',
                    'total' => round($total, 1),
                    'available' => round($available, 1),
                ];
            })
            ->values();

        $isOnLeaveToday = LeaveRequest::where('employee_id', $target->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->exists();

        $todayAttendance = Attendance::where('employee_id', $target->id)->whereDate('work_date', today())->first();

        return response()->json([
            'member' => new EmployeeResource($target),
            'leaves' => LeaveRequestResource::collection($leaves),
            'expenses' => ExpenseResource::collection($expenses),
            'open_tasks' => TaskResource::collection($openTasks),
            'recent_attendance' => AttendanceResource::collection($recentAttendance),
            'leave_balances' => $leaveBalances,
            'is_on_leave_today' => $isOnLeaveToday,
            'today_attendance' => $todayAttendance ? new AttendanceResource($todayAttendance) : null,
            'pending_leaves' => LeaveRequest::where('employee_id', $target->id)->where('status', 'pending')->count(),
            'pending_expenses' => Expense::where('employee_id', $target->id)->where('status', 'submitted')->count(),
        ]);
    }

    public function leaveApprovals(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $reportIds = $this->getReportIds($emp->id);

        if ($reportIds->isEmpty()) {
            return response()->json(['message' => 'You do not have any direct reports.'], 403);
        }

        $status = $request->get('status', 'pending');
        $query = LeaveRequest::with(['employee.position', 'leaveType', 'approver'])->whereIn('employee_id', $reportIds);

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $leaves = $query->orderByRaw("FIELD(status, 'pending','approved','rejected','cancelled')")
            ->orderByDesc('created_at')
            ->paginate(15);

        $leaves->getCollection()->transform(function ($leave) use ($reportIds) {
            $leave->_teammates_on_leave = $this->teammatesOnLeaveCount($leave, $reportIds);
            return $leave;
        });

        $counters = LeaveRequest::whereIn('employee_id', $reportIds)
            ->selectRaw("COUNT(*) as total, SUM(status = 'pending') as pending, SUM(status = 'approved') as approved, SUM(status = 'rejected') as rejected")
            ->first();

        return response()->json([
            'leaves' => LeaveRequestResource::collection($leaves->items()),
            'pagination' => ['current_page' => $leaves->currentPage(), 'last_page' => $leaves->lastPage(), 'total' => $leaves->total()],
            'counters' => [
                'total' => (int) $counters->total,
                'pending' => (int) $counters->pending,
                'approved' => (int) $counters->approved,
                'rejected' => (int) $counters->rejected,
            ],
        ]);
    }

    public function approveLeave(Request $request, int $leave): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $leaveReq = LeaveRequest::findOrFail($leave);
        $this->assertManagesEmployee($emp->id, $leaveReq->employee_id);

        if ($leaveReq->status !== 'pending') {
            return $this->fail('This leave request has already been processed.');
        }

        $data = $request->validate(['note' => 'nullable|string|max:500']);

        $balance = LeaveBalance::where('employee_id', $leaveReq->employee_id)
            ->where('leave_type_id', $leaveReq->leave_type_id)
            ->where('year', Carbon::parse($leaveReq->start_date)->year)
            ->first();

        if ($balance) {
            $net = $balance->available;
            if ($net < 0) {
                return $this->fail('Insufficient leave balance. Approving this would create a negative balance of '.abs($net).' day(s). Contact HR to adjust the balance first.');
            }
        }

        $threshold = (float) \App\Models\Tenant\Setting::get('leaves.second_approval_threshold_days', 5);
        $needsFinalSignoff = (float) $leaveReq->total_days >= $threshold;

        DB::connection('tenant')->beginTransaction();
        try {
            if ($needsFinalSignoff) {
                $leaveReq->update([
                    'status' => 'pending_final',
                    'approver_comments' => $data['note'] ?? null,
                ]);
                \App\Models\Tenant\LeaveApproval::create([
                    'leave_request_id' => $leaveReq->id,
                    'approver_id' => $request->user()->id,
                    'approval_level' => 1,
                    'decision' => 'approved',
                    'comments' => $data['note'] ?? null,
                    'decided_at' => now(),
                ]);
            } else {
                $leaveReq->update([
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'approver_comments' => $data['note'] ?? null,
                ]);
                $this->movePendingToUsed($leaveReq, $balance);
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team leave approve failed (API): '.$e->getMessage());

            return $this->fail('Could not approve the leave. Please try again.', 500);
        }

        if ($needsFinalSignoff) {
            NotificationService::notifyAdmins(
                "{$leaveReq->employee->full_name}'s leave ({$leaveReq->total_days} days) needs final sign-off",
                'leave.pending_final', 'calendar-check', '#F59E0B',
                route('admin.leaves.index', app(TenantManager::class)->current()->slug)
            );

            return response()->json(['success' => true, 'message' => "Approved — forwarded for final sign-off."]);
        }

        $employeeUser = User::where('employee_id', $leaveReq->employee_id)->first();
        if ($employeeUser) {
            NotificationService::leaveApproved($employeeUser, $request->user()->name,
                route('employee.team.approvals.leaves', app(TenantManager::class)->current()->slug));
        }
        try {
            app(MailService::class)->sendLeaveApproved($leaveReq->fresh(['employee', 'leaveType', 'approver']));
        } catch (\Throwable $e) {
            \Log::error('Team leave-approved email failed (API): '.$e->getMessage());
        }

        return response()->json(['success' => true, 'message' => "{$leaveReq->employee->full_name}'s leave approved."]);
    }

    public function rejectLeave(Request $request, int $leave): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $leaveReq = LeaveRequest::findOrFail($leave);
        $this->assertManagesEmployee($emp->id, $leaveReq->employee_id);

        if ($leaveReq->status !== 'pending') {
            return $this->fail('This leave request has already been processed.');
        }

        $data = $request->validate(['reason' => 'required|string|min:5|max:500']);

        DB::connection('tenant')->beginTransaction();
        try {
            $leaveReq->update([
                'status' => 'rejected',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'rejection_reason' => $data['reason'],
            ]);

            $balance = LeaveBalance::where('employee_id', $leaveReq->employee_id)
                ->where('leave_type_id', $leaveReq->leave_type_id)
                ->where('year', Carbon::parse($leaveReq->start_date)->year)
                ->first();

            if ($balance) {
                $balance->update(['pending' => max(0, (float) $balance->pending - (float) $leaveReq->total_days)]);
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team leave reject failed (API): '.$e->getMessage());

            return $this->fail('Could not reject the leave. Please try again.', 500);
        }

        $employeeUser = User::where('employee_id', $leaveReq->employee_id)->first();
        if ($employeeUser) {
            NotificationService::leaveRejected($employeeUser, $request->user()->name,
                route('employee.team.approvals.leaves', app(TenantManager::class)->current()->slug));
        }
        try {
            app(MailService::class)->sendLeaveRejected($leaveReq->fresh(['employee', 'leaveType']));
        } catch (\Throwable $e) {
            \Log::error('Team leave-rejected email failed (API): '.$e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Leave request rejected.']);
    }

    public function expenseApprovals(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $reportIds = $this->getReportIds($emp->id);

        if ($reportIds->isEmpty()) {
            return response()->json(['message' => 'You do not have any direct reports.'], 403);
        }

        $status = $request->get('status', 'submitted');
        $query = Expense::with(['employee.position', 'category'])->whereIn('employee_id', $reportIds);

        if (in_array($status, ['submitted', 'approved', 'rejected', 'paid'])) {
            $query->where('status', $status);
        } elseif ($status === 'all') {
            $query->whereNotIn('status', ['draft']);
        }

        $expenses = $query->orderByRaw("FIELD(status, 'submitted','approved','paid','rejected','cancelled')")
            ->orderByDesc('submitted_at')
            ->paginate(15);

        $counters = Expense::whereIn('employee_id', $reportIds)
            ->selectRaw("COUNT(*) as total, SUM(status = 'submitted') as submitted, SUM(status = 'approved') as approved, SUM(status = 'paid') as paid, SUM(status = 'rejected') as rejected")
            ->first();

        $pendingTotal = Expense::whereIn('employee_id', $reportIds)->where('status', 'submitted')->sum('amount');

        return response()->json([
            'expenses' => ExpenseResource::collection($expenses->items()),
            'pagination' => ['current_page' => $expenses->currentPage(), 'last_page' => $expenses->lastPage(), 'total' => $expenses->total()],
            'counters' => [
                'total' => (int) $counters->total,
                'submitted' => (int) $counters->submitted,
                'approved' => (int) $counters->approved,
                'paid' => (int) $counters->paid,
                'rejected' => (int) $counters->rejected,
            ],
            'pending_total' => (float) $pendingTotal,
        ]);
    }

    public function approveExpense(Request $request, int $expense): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $exp = Expense::findOrFail($expense);
        $this->assertManagesEmployee($emp->id, $exp->employee_id);

        if ($exp->status !== 'submitted') {
            return $this->fail('Only submitted expenses can be approved.');
        }

        $data = $request->validate(['note' => 'nullable|string|max:500']);

        DB::connection('tenant')->beginTransaction();
        try {
            $exp->update(['status' => 'approved', 'approved_at' => now()]);

            if (Schema::connection('tenant')->hasTable('expense_approvals')) {
                ExpenseApproval::create([
                    'expense_id' => $exp->id,
                    'approver_id' => $request->user()->id,
                    'approval_level' => 1,
                    'decision' => 'approved',
                    'comments' => $data['note'] ?? null,
                    'decided_at' => now(),
                ]);
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team expense approve failed (API): '.$e->getMessage());

            return $this->fail('Could not approve the expense. Please try again.', 500);
        }

        return response()->json(['success' => true, 'message' => "Expense {$exp->expense_number} approved."]);
    }

    public function rejectExpense(Request $request, int $expense): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $exp = Expense::findOrFail($expense);
        $this->assertManagesEmployee($emp->id, $exp->employee_id);

        if ($exp->status !== 'submitted') {
            return $this->fail('Only submitted expenses can be rejected.');
        }

        $data = $request->validate(['reason' => 'required|string|min:5|max:500']);

        DB::connection('tenant')->beginTransaction();
        try {
            $exp->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

            if (Schema::connection('tenant')->hasTable('expense_approvals')) {
                ExpenseApproval::create([
                    'expense_id' => $exp->id,
                    'approver_id' => $request->user()->id,
                    'approval_level' => 1,
                    'decision' => 'rejected',
                    'comments' => $data['reason'],
                    'decided_at' => now(),
                ]);
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Team expense reject failed (API): '.$e->getMessage());

            return $this->fail('Could not reject the expense. Please try again.', 500);
        }

        return response()->json(['success' => true, 'message' => "Expense {$exp->expense_number} rejected."]);
    }

    protected function employeeOrFail(Request $request)
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return $emp;
    }

    protected function assertManagesEmployee(int $managerEmployeeId, int $targetEmployeeId): Employee
    {
        $target = Employee::where('manager_id', $managerEmployeeId)->where('id', $targetEmployeeId)->first();
        abort_unless($target, 403, 'You do not manage this employee.');

        return $target;
    }

    protected function getReportIds(int $managerEmployeeId)
    {
        return Employee::where('manager_id', $managerEmployeeId)->pluck('id');
    }

    /**
     * How many OTHER employees in the given pool already have an approved (or awaiting-final-
     * signoff) leave overlapping the calendar week of this request's start date — a heads-up for
     * the approver, not a hard block.
     */
    protected function teammatesOnLeaveCount(LeaveRequest $leave, $employeeIdPool): int
    {
        $weekStart = Carbon::parse($leave->start_date)->startOfWeek();
        $weekEnd   = Carbon::parse($leave->start_date)->endOfWeek();

        return LeaveRequest::whereIn('employee_id', $employeeIdPool)
            ->where('employee_id', '!=', $leave->employee_id)
            ->whereIn('status', ['approved', 'pending_final'])
            ->where('start_date', '<=', $weekEnd->toDateString())
            ->where('end_date', '>=', $weekStart->toDateString())
            ->distinct()
            ->count('employee_id');
    }

    protected function fail(string $msg, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $msg], $status);
    }

    private function movePendingToUsed(LeaveRequest $leave, ?LeaveBalance $balance): void
    {
        if (! $balance) {
            $balance = LeaveBalance::firstOrCreate(
                ['employee_id' => $leave->employee_id, 'leave_type_id' => $leave->leave_type_id, 'year' => Carbon::parse($leave->start_date)->year],
                ['allocated' => 0, 'accrued' => 0, 'used' => 0, 'pending' => 0, 'carried_over' => 0, 'adjusted' => 0]
            );
        }

        $newPending = max(0, (float) $balance->pending - (float) $leave->total_days);
        $balance->update(['pending' => $newPending, 'used' => (float) $balance->used + (float) $leave->total_days]);
    }
}
