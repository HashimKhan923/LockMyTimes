<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveBalanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\LeaveTypeResource;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\LeaveType;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\TenantManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * JSON mirror of Employee/LeaveController — same eligibility rules
 * (notice days, max-consecutive, documentation, balance, overlap), same
 * models, no Blade/redirect branches.
 */
class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $year = (int) $request->get('year', now()->year);
        $status = $request->get('status');
        $typeId = $request->get('type');

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $emp->id)
            ->where('year', $year)
            ->get()
            ->filter(fn ($b) => $b->leaveType)
            ->map(function ($b) {
                $total = (float) ($b->allocated + $b->accrued + $b->carried_over + $b->adjusted);
                $available = $b->available;
                $usedPct = $total > 0 ? min(100, round((($b->used + $b->pending) / $total) * 100)) : 0;

                return (object) [
                    'id' => $b->id,
                    'type_id' => $b->leave_type_id,
                    'name' => $b->leaveType->name,
                    'color' => $b->leaveType->color ?? '#6C7DF7',
                    'is_paid' => (bool) $b->leaveType->is_paid,
                    'total' => round($total, 1),
                    'used' => round((float) $b->used, 1),
                    'pending' => round((float) $b->pending, 1),
                    'available' => round($available, 1),
                    'used_pct' => $usedPct,
                ];
            })
            ->values();

        $summary = [
            'available' => round((float) $balances->sum('available'), 1),
            'used' => round((float) $balances->sum('used'), 1),
            'pending' => round((float) $balances->sum('pending'), 1),
            'total' => round((float) $balances->sum('total'), 1),
        ];

        $requests = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $emp->id)
            ->whereYear('start_date', $year)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($typeId, fn ($q, $t) => $q->where('leave_type_id', $t))
            ->orderByDesc('start_date')
            ->paginate(15);

        $allTypes = LeaveType::query()
            ->when(Schema::connection('tenant')->hasColumn('leave_types', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        $availableYears = LeaveRequest::where('employee_id', $emp->id)
            ->selectRaw('YEAR(start_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json([
            'year' => $year,
            'balances' => LeaveBalanceResource::collection($balances),
            'summary' => $summary,
            'requests' => LeaveRequestResource::collection($requests->items()),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
            'leave_types' => LeaveTypeResource::collection($allTypes),
            'years' => $availableYears,
        ]);
    }

    public function show(Request $request, int $leave): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $lr = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $emp->id)
            ->findOrFail($leave);

        return response()->json(['leave' => new LeaveRequestResource($lr)]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'day_part' => 'nullable|in:full,full_day,first_half,second_half',
            'leave_type_id' => 'nullable|integer|exists:leave_types,id',
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $rawDp = $data['day_part'] ?? 'full_day';
        $dp = $rawDp === 'full' ? 'full_day' : $rawDp;

        $locationIds = $this->employeeLocationIds($emp);
        $workdays = $this->countWorkingDays($start, $end, $locationIds);
        $total = ($start->equalTo($end) && $dp !== 'full_day') ? 0.5 : (float) $workdays;

        $holidaysInRange = Holiday::visibleTo($locationIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get(['date', 'name'])
            ->map(fn ($h) => ['date' => Carbon::parse($h->date)->format('M j'), 'name' => $h->name]);

        $remaining = null;
        $available = null;
        if (! empty($data['leave_type_id'])) {
            $bal = LeaveBalance::where('employee_id', $emp->id)
                ->where('leave_type_id', $data['leave_type_id'])
                ->where('year', $start->year)
                ->first();
            if ($bal) {
                $available = $bal->available;
                $remaining = round($available - $total, 1);
            }
        }

        $warnings = [];
        if ($total === 0.0) $warnings[] = 'The selected range contains no working days.';
        if ($remaining !== null && $remaining < 0) {
            $warnings[] = 'This exceeds your available balance by '.abs($remaining).' day(s).';
        }

        return response()->json([
            'workdays' => $workdays,
            'total' => $total,
            'available' => $available !== null ? round($available, 1) : null,
            'remaining' => $remaining,
            'holidays' => $holidaysInRange,
            'warnings' => $warnings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'day_part' => ['nullable', 'in:full,full_day,first_half,second_half'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'contact_during_leave' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $type = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $errors = [];

        if ($type->notice_days_required > 0 && $start->isFuture()) {
            $noticeDays = (int) now()->startOfDay()->diffInDays($start->startOfDay(), false);
            if ($noticeDays < (int) $type->notice_days_required) {
                $errors['start_date'] = "This leave type requires at least {$type->notice_days_required} days' notice.";
            }
        }

        $minService = (int) ($type->min_service_months ?? 0);
        if ($minService > 0 && ($emp->service_months ?? 0) < $minService) {
            $errors['leave_type_id'] = "You need at least {$minService} months of service to request this leave type (you have {$emp->service_months}).";
        }

        $rawPart = $data['day_part'] ?? 'full_day';
        $dayPart = $rawPart === 'full' ? 'full_day' : $rawPart;
        $workdays = $this->countWorkingDays($start, $end, $this->employeeLocationIds($emp));
        if ($workdays === 0) {
            $errors['start_date'] = 'No working days in the selected range.';
        }

        $totalDays = ($start->equalTo($end) && $dayPart !== 'full_day') ? 0.5 : (float) $workdays;

        if ($type->max_consecutive_days > 0 && $totalDays > $type->max_consecutive_days) {
            $errors['end_date'] = "Cannot exceed {$type->max_consecutive_days} consecutive days for this leave type.";
        }

        if ($type->requires_documentation && ! $request->hasFile('attachment')) {
            $errors['attachment'] = 'This leave type requires supporting documentation.';
        }

        $balance = LeaveBalance::where('employee_id', $emp->id)
            ->where('leave_type_id', $type->id)
            ->where('year', $start->year)
            ->first();

        if (! $balance && (float) $type->default_days_per_year > 0) {
            $balance = LeaveBalance::create([
                'employee_id' => $emp->id,
                'leave_type_id' => $type->id,
                'year' => $start->year,
                'allocated' => $type->default_days_per_year,
                'accrued' => 0,
                'used' => 0,
                'pending' => 0,
                'carried_over' => 0,
                'adjusted' => 0,
            ]);
        }

        if ($balance) {
            $available = $balance->available;
            if ($type->is_paid && $totalDays > $available) {
                $errors['leave_type_id'] = "Insufficient balance. You have {$available} day(s) available but requested {$totalDays}.";
            }
        } elseif ($type->is_paid) {
            $errors['leave_type_id'] = 'Contact HR to allocate your leave balance for this leave type.';
        }

        $overlap = LeaveRequest::where('employee_id', $emp->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where('start_date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    });
            })
            ->exists();

        if ($overlap) {
            $errors['start_date'] = 'You already have a leave request that overlaps with this date range.';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $attachments = [];
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store("leaves/{$emp->id}/".now()->format('Y/m'), 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                ];
            }

            $needsApproval = (bool) $type->requires_approval;
            $status = $needsApproval ? 'pending' : 'approved';

            $lr = LeaveRequest::create([
                'employee_id' => $emp->id,
                'leave_type_id' => $type->id,
                'request_number' => LeaveRequest::generateNumber(),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'total_days' => $totalDays,
                'day_part' => $dayPart,
                'reason' => $data['reason'],
                'contact_during_leave' => $data['contact_during_leave'] ?? null,
                'attachments' => $attachments ?: null,
                'status' => $status,
                'approved_by' => $needsApproval ? null : $request->user()->id,
                'approved_at' => $needsApproval ? null : now(),
            ]);

            if ($balance) {
                if ($needsApproval) {
                    $balance->increment('pending', $totalDays);
                } else {
                    $balance->increment('used', $totalDays);
                }
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Leave store failed (API): '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());

            return response()->json(['success' => false, 'message' => 'Could not submit your leave request. Please try again.'], 500);
        }

        // Best-effort — a notification failure must never turn an already-
        // committed leave request into a false "failed, try again" response.
        if ($needsApproval) {
            try {
                app(MailService::class)->sendLeaveRequested($lr->load(['employee', 'leaveType']));

                $managerUser = $emp->manager?->user;
                if ($managerUser) {
                    NotificationService::send(
                        $managerUser,
                        "{$emp->full_name} submitted a leave request",
                        'leave.requested',
                        'calendar-clock',
                        '#F59E0B',
                        route('employee.team.approvals.leaves', app(TenantManager::class)->current()->slug)
                    );
                }
            } catch (\Throwable $e) {
                \Log::error('Leave-requested notification failed (API): '.$e->getMessage());
            }
        } else {
            try {
                app(MailService::class)->sendLeaveApproved($lr->fresh(['employee', 'leaveType']));
                if ($emp->user) {
                    NotificationService::leaveApproved($emp->user, 'Auto-approval',
                        route('employee.leaves.index', app(TenantManager::class)->current()->slug));
                }
            } catch (\Throwable $e) {
                \Log::error('Leave-auto-approved notification failed (API): '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => $needsApproval
                ? "Leave request {$lr->request_number} submitted. Awaiting approval."
                : "Leave {$lr->request_number} recorded.",
            'leave' => new LeaveRequestResource($lr->load(['leaveType', 'approver'])),
        ]);
    }

    public function cancel(Request $request, int $leave): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $lr = LeaveRequest::where('employee_id', $emp->id)->findOrFail($leave);

        if (! in_array($lr->status, ['pending', 'approved'])) {
            return $this->fail('Only pending or approved leaves can be cancelled.');
        }

        if ($lr->status === 'approved' && Carbon::parse($lr->start_date)->isPast()) {
            return $this->fail('Cannot cancel a leave that has already started.');
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $prev = $lr->status;
            $lr->update(['status' => 'cancelled']);

            $balance = LeaveBalance::where('employee_id', $emp->id)
                ->where('leave_type_id', $lr->leave_type_id)
                ->where('year', Carbon::parse($lr->start_date)->year)
                ->first();

            if ($balance) {
                if ($prev === 'pending') {
                    $balance->decrement('pending', (float) $lr->total_days);
                } elseif ($prev === 'approved') {
                    $balance->decrement('used', (float) $lr->total_days);
                }
            }

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();

            return $this->fail('Could not cancel. Please try again.');
        }

        return response()->json(['success' => true, 'message' => 'Leave cancelled.']);
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

    protected function countWorkingDays(Carbon $from, Carbon $to, array $locationIds = []): int
    {
        $holidayDates = Holiday::visibleTo($locationIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $count = 0;
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if ($d->isWeekend()) continue;
            if ($holidayDates->has($d->toDateString())) continue;
            $count++;
        }

        return $count;
    }

    /** IDs of every location this employee is assigned to (primary location_id + employee_locations pivot). */
    protected function employeeLocationIds($emp): array
    {
        return $emp->locations()->pluck('locations.id')
            ->when($emp->location_id, fn ($c) => $c->push($emp->location_id))
            ->unique()
            ->values()
            ->all();
    }
}
