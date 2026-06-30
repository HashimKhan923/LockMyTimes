<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\LeaveType;
use App\Models\Tenant\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    /* ================================================================
     | INDEX — Balances + History
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $year   = (int) ($request->get('year', now()->year));
        $status = $request->get('status'); // pending | approved | rejected | cancelled
        $typeId = $request->get('type');

        /* ───────── Balances for the selected year ───────── */
        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $emp->id)
            ->where('year', $year)
            ->get()
            ->filter(fn ($b) => $b->leaveType) // skip orphans
            ->map(function ($b) {
                $total     = (float) ($b->allocated + $b->accrued + $b->carried_over + $b->adjusted);
                $available = (float) ($total - $b->used - $b->pending);
                $usedPct   = $total > 0 ? min(100, round((($b->used + $b->pending) / $total) * 100)) : 0;
                return (object) [
                    'id'        => $b->id,
                    'type_id'   => $b->leave_type_id,
                    'name'      => $b->leaveType->name,
                    'color'     => $b->leaveType->color ?? '#6C7DF7',
                    'is_paid'   => (bool) $b->leaveType->is_paid,
                    'total'     => round($total, 1),
                    'used'      => round((float) $b->used, 1),
                    'pending'   => round((float) $b->pending, 1),
                    'available' => round($available, 1),
                    'used_pct'  => $usedPct,
                ];
            })
            ->values();

        /* ───────── Top-line summary ───────── */
        $summary = (object) [
            'available'    => round((float) $balances->sum('available'), 1),
            'used'         => round((float) $balances->sum('used'), 1),
            'pending'      => round((float) $balances->sum('pending'), 1),
            'total'        => round((float) $balances->sum('total'), 1),
        ];

        /* ───────── History ───────── */
        $requests = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $emp->id)
            ->whereYear('start_date', $year)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($typeId, fn ($q, $t) => $q->where('leave_type_id', $t))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        /* ───────── Quick counters for the filter chips ───────── */
        $counters = LeaveRequest::where('employee_id', $emp->id)
            ->whereYear('start_date', $year)
            ->selectRaw("
                SUM(status = 'pending')   as p,
                SUM(status = 'approved')  as a,
                SUM(status = 'rejected')  as r,
                SUM(status = 'cancelled') as c
            ")
            ->first();

        $allTypes = LeaveType::query()
            ->when(Schema::connection('tenant')->hasColumn('leave_types', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->orderBy('name')->get(['id', 'name', 'color']);

        $availableYears = LeaveRequest::where('employee_id', $emp->id)
            ->selectRaw('YEAR(start_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('employee.leaves.index', [
            'tenantSlug'   => $tenant,
            'emp'          => $emp,
            'year'         => $year,
            'status'       => $status,
            'typeId'       => $typeId,
            'balances'     => $balances,
            'summary'      => $summary,
            'requests'     => $requests,
            'counters'     => $counters,
            'allTypes'     => $allTypes,
            'years'        => $availableYears,
        ]);
    }

    /* ================================================================
     | CREATE — Apply form
     |================================================================*/
    public function create(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $year = now()->year;

        // Active leave types + their balance for this employee/year
        $types = LeaveType::query()
            ->when(Schema::connection('tenant')->hasColumn('leave_types', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        $balancesByType = LeaveBalance::where('employee_id', $emp->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        $types = $types->map(function ($t) use ($balancesByType) {
            $b = $balancesByType->get($t->id);
            if ($b) {
                $total     = (float) ($b->allocated + $b->accrued + $b->carried_over + $b->adjusted);
                $available = (float) ($total - $b->used - $b->pending);
            } else {
                // No balance row yet — show the leave type default so employee knows their entitlement
                $total     = (float) $t->default_days_per_year;
                $available = $total;
            }
            $t->_total     = round($total, 1);
            $t->_available = round($available, 1);
            return $t;
        });

        return view('employee.leaves.create', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'types'      => $types,
            'minDate'    => now()->subDays(7)->toDateString(),
        ]);
    }

    /* ================================================================
     | STORE — Submit a new leave request
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'leave_type_id'        => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['required', 'date', 'after_or_equal:start_date'],
            'day_part'             => ['nullable', 'in:full,full_day,first_half,second_half'],
            'reason'               => ['required', 'string', 'min:5', 'max:1000'],
            'contact_during_leave' => ['nullable', 'string', 'max:255'],
            'attachment'           => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $type = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);

        /* ───────── Validation gates from LeaveType config ───────── */
        $errors = [];

        // Notice days
        if ($type->notice_days_required > 0 && $start->isFuture()) {
            $noticeDays = (int) now()->startOfDay()->diffInDays($start->startOfDay(), false);
            if ($noticeDays < (int) $type->notice_days_required) {
                $errors['start_date'] = "This leave type requires at least {$type->notice_days_required} days' notice.";
            }
        }

        // Normalize day_part to DB enum values (form sends 'full', DB expects 'full_day')
        $rawPart  = $data['day_part'] ?? 'full_day';
        $dayPart  = ($rawPart === 'full') ? 'full_day' : $rawPart;
        $workdays = $this->countWorkingDays($start, $end);
        if ($workdays === 0) {
            $errors['start_date'] = 'No working days in the selected range.';
        }

        if ($start->equalTo($end) && $dayPart !== 'full_day') {
            $totalDays = 0.5;
        } else {
            $totalDays = (float) $workdays;
        }

        // Max consecutive days
        if ($type->max_consecutive_days > 0 && $totalDays > $type->max_consecutive_days) {
            $errors['end_date'] = "Cannot exceed {$type->max_consecutive_days} consecutive days for this leave type.";
        }

        // Documentation requirement
        if ($type->requires_documentation && ! $request->hasFile('attachment')) {
            $errors['attachment'] = 'This leave type requires supporting documentation.';
        }

        // Balance check (for current year only — balances are year-scoped)
        $balance = LeaveBalance::where('employee_id', $emp->id)
            ->where('leave_type_id', $type->id)
            ->where('year', $start->year)
            ->first();

        // Auto-initialize balance from leave type default if no row exists yet
        if (! $balance && (float) $type->default_days_per_year > 0) {
            $balance = LeaveBalance::create([
                'employee_id'   => $emp->id,
                'leave_type_id' => $type->id,
                'year'          => $start->year,
                'allocated'     => $type->default_days_per_year,
                'accrued'       => 0,
                'used'          => 0,
                'pending'       => 0,
                'carried_over'  => 0,
                'adjusted'      => 0,
            ]);
        }

        if ($balance) {
            $available = (float) ($balance->allocated + $balance->accrued + $balance->carried_over + $balance->adjusted - $balance->used - $balance->pending);
            if ($type->is_paid && $totalDays > $available) {
                $errors['leave_type_id'] = "Insufficient balance. You have {$available} day(s) available but requested {$totalDays}.";
            }
        } elseif ($type->is_paid) {
            $errors['leave_type_id'] = 'Contact HR to allocate your leave balance for this leave type.';
        }

        // Overlap check — pending or approved leaves overlapping the range
        $overlap = LeaveRequest::where('employee_id', $emp->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('end_date',   [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($qq) use ($start, $end) {
                      $qq->where('start_date', '<=', $start->toDateString())
                         ->where('end_date',   '>=', $end->toDateString());
                  });
            })
            ->exists();

        if ($overlap) {
            $errors['start_date'] = 'You already have a leave request that overlaps with this date range.';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        /* ───────── Persist ───────── */
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
            $status        = $needsApproval ? 'pending' : 'approved';

            $lr = LeaveRequest::create([
                'employee_id'          => $emp->id,
                'leave_type_id'        => $type->id,
                'request_number'       => LeaveRequest::generateNumber(),
                'start_date'           => $start->toDateString(),
                'end_date'             => $end->toDateString(),
                'total_days'           => $totalDays,
                'day_part'             => $dayPart,
                'reason'               => $data['reason'],
                'contact_during_leave' => $data['contact_during_leave'] ?? null,
                'attachments'          => $attachments ?: null,
                'status'               => $status,
                'approved_by'          => $needsApproval ? null : auth()->id(),
                'approved_at'          => $needsApproval ? null : now(),
            ]);

            // Update balance: add to pending (if approval required) or used (auto-approved)
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
            \Log::error('Leave store failed: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return back()
                ->withInput()
                ->with('error', 'Could not submit your leave request. Please try again.');
        }

        return redirect()
            ->route('employee.leaves.index', $tenant)
            ->with('success', $needsApproval
                ? "Leave request {$lr->request_number} submitted. Awaiting approval."
                : "Leave {$lr->request_number} recorded.");
    }

    /* ================================================================
     | SHOW (JSON for drawer)
     |================================================================*/
    public function show(string $tenant, int $leave)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $lr = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $emp->id)
            ->findOrFail($leave);

        return response()->json([
            'html' => view('employee.leaves._detail_drawer', [
                'tenantSlug' => $tenant,
                'lr'         => $lr,
            ])->render(),
        ]);
    }

    /* ================================================================
     | CANCEL (own pending requests)
     |================================================================*/
    public function cancel(string $tenant, int $leave, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $lr = LeaveRequest::where('employee_id', $emp->id)->findOrFail($leave);

        if (! in_array($lr->status, ['pending', 'approved'])) {
            return $this->fail('Only pending or approved leaves can be cancelled.');
        }

        // Approved + already started? block.
        if ($lr->status === 'approved' && Carbon::parse($lr->start_date)->isPast()) {
            return $this->fail('Cannot cancel a leave that has already started.');
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $prev = $lr->status;
            $lr->update(['status' => 'cancelled']);

            // Restore the balance
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

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Leave cancelled.']);
        }

        return back()->with('success', "Leave {$lr->request_number} cancelled.");
    }

    /* ================================================================
     | AJAX — Calculate working days for a date range
     |================================================================*/
    public function calculate(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'day_part'      => 'nullable|in:full,full_day,first_half,second_half',
            'leave_type_id' => 'nullable|integer|exists:leave_types,id',
        ]);

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $rawDp = $data['day_part'] ?? 'full_day';
        $dp    = ($rawDp === 'full') ? 'full_day' : $rawDp;

        $workdays = $this->countWorkingDays($start, $end);
        $total    = ($start->equalTo($end) && $dp !== 'full_day') ? 0.5 : (float) $workdays;

        $holidaysInRange = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
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
                $available = (float) ($bal->allocated + $bal->accrued + $bal->carried_over + $bal->adjusted - $bal->used - $bal->pending);
                $remaining = round($available - $total, 1);
            }
        }

        // Warnings
        $warnings = [];
        if ($total === 0.0) $warnings[] = 'The selected range contains no working days.';
        if ($remaining !== null && $remaining < 0) {
            $warnings[] = 'This exceeds your available balance by '.abs($remaining).' day(s).';
        }

        return response()->json([
            'workdays'  => $workdays,
            'total'     => $total,
            'available' => $available !== null ? round($available, 1) : null,
            'remaining' => $remaining,
            'holidays'  => $holidaysInRange,
            'warnings'  => $warnings,
        ]);
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    protected function fail(string $msg, int $status = 422)
    {
        if (request()->wantsJson()) {
            return response()->json(['success' => false, 'message' => $msg], $status);
        }
        return back()->with('error', $msg);
    }

    /** Count Mon–Fri days excluding holidays between two dates inclusive. */
    protected function countWorkingDays(Carbon $from, Carbon $to): int
    {
        $weekStartsMon = (bool) Setting::get('attendance.week_starts_monday', true);

        $holidayDates = Holiday::whereBetween('date', [$from->toDateString(), $to->toDateString()])
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
}