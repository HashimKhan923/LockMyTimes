<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\LeaveApproval;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use App\Services\ExportService;
use App\Services\MailService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    /* ================================================================
     | INDEX — dashboard overview
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $status = $request->get('status', 'pending');
        $month  = $request->get('month', now()->format('Y-m'));

        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($empId = $request->get('employee')) {
            $query->where('employee_id', $empId);
        }

        if ($typeId = $request->get('type')) {
            $query->where('leave_type_id', $typeId);
        }

        $requests   = $query->paginate(20)->withQueryString();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();
        $employees  = Employee::active()->orderBy('first_name')->get();

        // Team conflict heads-up: how many other people in the same department are already
        // off during the same calendar week as each request.
        $requests->getCollection()->transform(function ($leave) {
            $deptId = $leave->employee?->department_id;
            $leave->_teammates_on_leave = $deptId
                ? $this->teammatesOnLeaveCount($leave, Employee::where('department_id', $deptId)->pluck('id'))
                : null;
            return $leave;
        });

        $stats = [
            'pending'        => LeaveRequest::where('status', 'pending')->count(),
            'approved'       => LeaveRequest::where('status', 'approved')
                                    ->whereMonth('start_date', now()->month)->count(),
            'on_leave_today' => LeaveRequest::where('status', 'approved')
                                    ->where('start_date', '<=', today())
                                    ->where('end_date', '>=', today())->count(),
            'rejected'       => LeaveRequest::where('status', 'rejected')
                                    ->whereMonth('created_at', now()->month)->count(),
        ];

        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $calendarLeaves = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date',   '>=', $start->toDateString())
            ->get();

        return view('admin.leaves.index', compact(
            'requests', 'leaveTypes', 'employees', 'stats',
            'calendarLeaves', 'start', 'end', 'month', 'status', 'tenant'
        ));
    }

    /* ================================================================
     | STORE — admin creates leave on behalf of employee (auto-approved)
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string|max:500',
            'is_half_day'   => 'boolean',
            'half_part'     => 'nullable|in:first_half,second_half',
        ]);

        $isHalfDay = $request->boolean('is_half_day');

        $days = $this->calculateWorkingDays(
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
            $isHalfDay
        );

        $lastId        = LeaveRequest::max('id') ?? 0;
        $requestNumber = 'LR-' . now()->format('Y') . '-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);

        $leave = LeaveRequest::create([
            'employee_id'    => $data['employee_id'],
            'leave_type_id'  => $data['leave_type_id'],
            'request_number' => $requestNumber,
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'total_days'     => $days,
            'day_part'       => $isHalfDay ? ($data['half_part'] ?? 'first_half') : 'full_day',
            'reason'         => $data['reason'] ?? null,
            'status'         => 'approved',
            'approved_by'    => auth()->id(),
            'approved_at'    => now(),
        ]);

        $this->deductUsed($leave);

        $employeeUser = User::where('employee_id', $leave->employee_id)->first();
        if ($employeeUser) {
            NotificationService::leaveApproved($employeeUser, auth()->user()->name,
                route('admin.leaves.index', $tenant));
        }

        return back()->with('success', "Leave created and approved for {$leave->employee->full_name}.");
    }

    /* ================================================================
     | APPROVE
     |================================================================*/
    public function approve(string $tenant, Request $request, LeaveRequest $leave)
    {
        // 'pending_final' = a manager already approved a long request and escalated it here for
        // final sign-off (see TeamController::approveLeave); 'pending' = the normal single-step
        // case (no manager involved, or a short request).
        if (! in_array($leave->status, ['pending', 'pending_final'], true)) {
            return back()->with('error', 'This leave request has already been processed.');
        }

        $wasPendingFinal = $leave->status === 'pending_final';

        $allowNegative = (bool) Setting::get('leaves.allow_negative_balance', false);

        $balance = LeaveBalance::where('employee_id',   $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year',          Carbon::parse($leave->start_date)->year)
            ->first();

        if (! $allowNegative && $balance) {
            // Full formula: pending already includes this leave's days
            $net = $balance->available;
            if ($net < 0) {
                return back()->with('error',
                    "Insufficient balance. Approving this would result in a negative balance of " . abs($net) . " day(s)."
                );
            }
        }

        $leave->update([
            'status'           => 'approved',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'approver_comments'=> $request->get('note'),
        ]);

        LeaveApproval::create([
            'leave_request_id' => $leave->id,
            'approver_id'      => auth()->id(),
            'approval_level'   => $wasPendingFinal ? 2 : 1,
            'decision'         => 'approved',
            'comments'         => $request->get('note'),
            'decided_at'       => now(),
        ]);

        // Move from pending used
        $this->movePendingToUsed($leave, $balance);

        $employeeUser = User::where('employee_id', $leave->employee_id)->first();
        if ($employeeUser) {
            NotificationService::leaveApproved($employeeUser, auth()->user()->name,
                route('admin.leaves.index', $tenant));
        }

        app(MailService::class)->sendLeaveApproved($leave->fresh(['employee', 'leaveType', 'approver']));

        return back()->with('success', "{$leave->employee->full_name}'s leave approved.");
    }

    /* ================================================================
     | AUTO-APPROVE pending leaves that qualify
     |================================================================*/
    public function autoApprovePending(string $tenant): int
    {
        $threshold = (int) Setting::get('leaves.auto_approve_under_days', 0);
        if ($threshold <= 0) return 0;

        $approved = 0;
        LeaveRequest::where('status', 'pending')
            ->where('total_days', '<=', $threshold)
            ->get()
            ->each(function ($leave) use (&$approved) {
                $leave->update([
                    'status'           => 'approved',
                    'approved_by'      => null,
                    'approved_at'      => now(),
                    'approver_comments'=> 'Auto-approved (within auto-approve threshold)',
                ]);

                $balance = LeaveBalance::where('employee_id',   $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year',          Carbon::parse($leave->start_date)->year)
                    ->first();

                $this->movePendingToUsed($leave, $balance);

                $employeeUser = User::where('employee_id', $leave->employee_id)->first();
                if ($employeeUser) {
                    NotificationService::leaveApproved($employeeUser, 'Auto-approval',
                        route('admin.leaves.index', $tenant));
                }
                try {
                    app(MailService::class)->sendLeaveApproved($leave->fresh(['employee', 'leaveType']));
                } catch (\Throwable $e) {
                    \Log::error('Leave auto-approve email failed: '.$e->getMessage());
                }

                $approved++;
            });

        return $approved;
    }

    /* ================================================================
     | REJECT
     |================================================================*/
    public function reject(string $tenant, Request $request, LeaveRequest $leave)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (! in_array($leave->status, ['pending', 'pending_final'], true)) {
            return back()->with('error', 'This leave request has already been processed.');
        }

        $wasPendingFinal = $leave->status === 'pending_final';

        $leave->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);

        LeaveApproval::create([
            'leave_request_id' => $leave->id,
            'approver_id'      => auth()->id(),
            'approval_level'   => $wasPendingFinal ? 2 : 1,
            'decision'         => 'rejected',
            'comments'         => $request->reason,
            'decided_at'       => now(),
        ]);

        // Release pending balance
        $balance = LeaveBalance::where('employee_id',   $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year',          Carbon::parse($leave->start_date)->year)
            ->first();

        if ($balance) {
            $balance->decrement('pending', (float) $leave->total_days);
        }

        $employeeUser = User::where('employee_id', $leave->employee_id)->first();
        if ($employeeUser) {
            NotificationService::leaveRejected($employeeUser, auth()->user()->name,
                route('admin.leaves.index', $tenant));
        }

        app(MailService::class)->sendLeaveRejected($leave->fresh(['employee', 'leaveType']));

        return back()->with('success', "Leave request rejected.");
    }

    /* ================================================================
     | CANCEL — admin cancels a leave
     |================================================================*/
    public function cancel(string $tenant, LeaveRequest $leave)
    {
        if (! in_array($leave->status, ['pending', 'approved'])) {
            return back()->with('error', 'Cannot cancel this leave.');
        }

        $prevStatus = $leave->status;
        $leave->update(['status' => 'cancelled']);

        $balance = LeaveBalance::where('employee_id',   $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year',          Carbon::parse($leave->start_date)->year)
            ->first();

        if ($balance) {
            if ($prevStatus === 'pending') {
                $balance->decrement('pending', (float) $leave->total_days);
            } elseif ($prevStatus === 'approved') {
                $balance->decrement('used', (float) $leave->total_days);
            }
        }

        app(MailService::class)->sendLeaveCancelled($leave->fresh(['employee', 'leaveType']));

        return back()->with('success', 'Leave cancelled and balance restored.');
    }

    /* ================================================================
     | BALANCES — view / edit employee balances
     |================================================================*/
    public function balances(string $tenant, Request $request)
    {
        $employees  = Employee::active()->with('leaveBalances.leaveType')
            ->orderBy('first_name')->paginate(20);
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('admin.leaves.balances', compact('employees', 'leaveTypes', 'tenant'));
    }

    public function updateBalance(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'allocated'     => 'required|numeric|min:0',
            'adjustment'    => 'nullable|numeric',
            'note'          => 'nullable|string|max:255',
        ]);

        $balance = LeaveBalance::updateOrCreate(
            [
                'employee_id'   => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'year'          => now()->year,
            ],
            ['allocated' => $data['allocated']]
        );

        if (! empty($data['adjustment'])) {
            $balance->increment('adjusted', (float) $data['adjustment']);
        }

        return back()->with('success', 'Leave balance updated.');
    }

    /* ================================================================
     | LEAVE TYPES — CRUD
     |================================================================*/
    public function types(string $tenant)
    {
        $types = LeaveType::withCount('leaveRequests')->orderBy('name')->get();
        return view('admin.leaves.types', compact('types', 'tenant'));
    }

    public function storeType(string $tenant, Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:100',
            'code'                  => 'required|string|max:20|unique:leave_types,code',
            'default_days_per_year' => 'required|numeric|min:0',
            'color'                 => 'nullable|string|max:7',
            'description'           => 'nullable|string',
            'max_carryover_days'    => 'nullable|numeric|min:0',
            'notice_days_required'  => 'nullable|integer|min:0',
            'max_consecutive_days'  => 'nullable|integer|min:0',
        ]);

        $type = LeaveType::create(array_merge($validated, [
            'is_active'              => true,
            'is_paid'                => $request->boolean('is_paid'),
            'requires_documentation' => $request->boolean('requires_documentation'),
            'requires_approval'      => $request->boolean('requires_approval', true),
        ]));

        // Auto-seed balance rows for all active employees
        if ((float) $type->default_days_per_year > 0) {
            $this->seedBalancesForType($type);
        }

        return back()->with('success', 'Leave type created and balances initialized for all employees.');
    }

    public function updateType(string $tenant, Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:100',
            'default_days_per_year' => 'required|numeric|min:0',
            'color'                 => 'nullable|string|max:7',
            'max_carryover_days'    => 'nullable|numeric|min:0',
            'notice_days_required'  => 'nullable|integer|min:0',
            'max_consecutive_days'  => 'nullable|integer|min:0',
        ]);

        $leaveType->update(array_merge($validated, [
            'is_paid'                => $request->boolean('is_paid'),
            'requires_documentation' => $request->boolean('requires_documentation'),
            'requires_approval'      => $request->boolean('requires_approval'),
            'is_active'              => $request->boolean('is_active'),
        ]));

        // Seed any employees who don't have a balance row yet
        $seeded = 0;
        if ($leaveType->is_active && (float) $leaveType->default_days_per_year > 0) {
            $seeded = $this->seedBalancesForType($leaveType);
        }

        $msg = 'Leave type updated.';
        if ($seeded > 0) $msg .= " Allocated balance to {$seeded} new employee(s).";
        return back()->with('success', $msg);
    }

    /* ================================================================
     | SYNC BALANCES — initialize missing LeaveBalance rows for all
     | active employees × all active leave types for the current year
     |================================================================*/
    public function syncBalances(string $tenant, Request $request)
    {
        $year  = (int) $request->get('year', now()->year);
        $count = 0;

        $types     = LeaveType::where('is_active', true)->get();
        $employees = Employee::active()->pluck('id');

        foreach ($types as $type) {
            if ((float) $type->default_days_per_year <= 0) continue;
            foreach ($employees as $empId) {
                $existing = LeaveBalance::where('employee_id', $empId)
                    ->where('leave_type_id', $type->id)
                    ->where('year', $year)
                    ->exists();
                if (! $existing) {
                    LeaveBalance::create([
                        'employee_id'   => $empId,
                        'leave_type_id' => $type->id,
                        'year'          => $year,
                        'allocated'     => $type->default_days_per_year,
                        'accrued'       => 0,
                        'used'          => 0,
                        'pending'       => 0,
                        'carried_over'  => 0,
                        'adjusted'      => 0,
                    ]);
                    $count++;
                }
            }
        }

        return back()->with('success', "Synced {$count} new balance record(s) for {$year}.");
    }

    /* ================================================================
     | HOLIDAYS — CRUD
     |================================================================*/
    public function holidays(string $tenant, Request $request)
    {
        $year     = $request->get('year', now()->year);
        $holidays = Holiday::whereYear('date', $year)->orderBy('date')->get();
        return view('admin.leaves.holidays', compact('holidays', 'year', 'tenant'));
    }

    public function storeHoliday(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'date'        => 'required|date|unique:holidays,date',
            'type'        => 'required|in:federal,state,company,religious,optional',
            'is_paid'     => 'boolean',
            'description' => 'nullable|string',
        ]);

        Holiday::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Holiday added.');
    }

    public function destroyHoliday(string $tenant, Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday removed.');
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    /** Create missing LeaveBalance rows for all active employees for a given type + current year. */
    private function seedBalancesForType(LeaveType $type, int $year = null): int
    {
        $year  = $year ?? now()->year;
        $count = 0;
        $employees = Employee::active()->pluck('id');

        foreach ($employees as $empId) {
            $exists = LeaveBalance::where('employee_id', $empId)
                ->where('leave_type_id', $type->id)
                ->where('year', $year)
                ->exists();
            if (! $exists) {
                LeaveBalance::create([
                    'employee_id'   => $empId,
                    'leave_type_id' => $type->id,
                    'year'          => $year,
                    'allocated'     => $type->default_days_per_year,
                    'accrued'       => 0,
                    'used'          => 0,
                    'pending'       => 0,
                    'carried_over'  => 0,
                    'adjusted'      => 0,
                ]);
                $count++;
            }
        }
        return $count;
    }

    /** Move days from the pending bucket to the used bucket on approval. */
    private function movePendingToUsed(LeaveRequest $leave, ?LeaveBalance $balance): void
    {
        if (! $balance) {
            // Balance row might not exist for unpaid/unlimited leave types — just create used entry
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year'          => Carbon::parse($leave->start_date)->year,
                ],
                ['allocated' => 0, 'accrued' => 0, 'used' => 0, 'pending' => 0, 'carried_over' => 0, 'adjusted' => 0]
            );
        }

        DB::connection('tenant')->transaction(function () use ($balance, $leave) {
            // Decrement pending (was held when employee submitted)
            $newPending = max(0, (float) $balance->pending - (float) $leave->total_days);
            $balance->update([
                'pending' => $newPending,
                'used'    => (float) $balance->used + (float) $leave->total_days,
            ]);
        });
    }

    /** Direct deduct from used — for admin-created leaves (no pending stage). */
    private function deductUsed(LeaveRequest $leave): void
    {
        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id'   => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'year'          => Carbon::parse($leave->start_date)->year,
            ],
            ['allocated' => 0, 'accrued' => 0, 'used' => 0, 'pending' => 0, 'carried_over' => 0, 'adjusted' => 0]
        );

        $balance->increment('used', (float) $leave->total_days);
    }

    /**
     * How many OTHER employees in the given pool already have an approved (or awaiting-final-
     * signoff) leave overlapping the calendar week of this request's start date — a heads-up for
     * the approver, not a hard block.
     */
    private function teammatesOnLeaveCount(LeaveRequest $leave, $employeeIdPool): int
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

    protected function calculateWorkingDays(Carbon $start, Carbon $end, bool $isHalfDay = false): float
    {
        if ($isHalfDay) return 0.5;

        $holidays = Holiday::where('is_active', true)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (! $date->isWeekend() && ! in_array($date->toDateString(), $holidays)) {
                $days++;
            }
        }

        return $days;
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $leaves = LeaveRequest::with(['employee.department', 'leaveType'])
            ->latest()->get();

        $columns = ['Employee', 'Department', 'Leave Type', 'From', 'To', 'Days', 'Status', 'Requested On'];

        $rows = $leaves->map(fn($l) => [
            $l->employee->full_name ?? '-',
            $l->employee->department?->name ?? '-',
            $l->leaveType->name ?? '-',
            $l->start_date->format('Y-m-d'),
            $l->end_date->format('Y-m-d'),
            $l->total_days ?? '-',
            ucfirst($l->status),
            $l->created_at->format('Y-m-d'),
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Leave Requests Report', $columns, $rows, 'leaves-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'leaves-'.now()->format('Y-m-d').'.xlsx');
    }
}
