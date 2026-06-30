<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\AnnouncementRead;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\AttendanceBreak;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\LeaveBalance;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\ShiftAssignment;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAssignee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /* ================================================================
     | DASHBOARD
     |================================================================*/
    public function index(string $tenant)
    {
        $user = auth()->user();
        $emp  = $user?->employee;

        // Safety: if the user has no employee record (shouldn't happen
        // post-auth but be defensive), fall back to a placeholder view.
        if (! $emp) {
            return view('employee.dashboard', [
                'tenantSlug'   => $tenant,
                'noEmployee'   => true,
            ]);
        }

        $emp->load(['department', 'position', 'location', 'manager']);

        $today    = Carbon::today();
        $now      = Carbon::now();

        /* ───────── Today's attendance + active break ───────── */
        $attendance = Attendance::with('location')
            ->where('employee_id', $emp->id)
            ->where('work_date', $today->toDateString())
            ->first();

        $activeBreak = $attendance
            ? AttendanceBreak::where('attendance_id', $attendance->id)
                ->whereNull('end_at')
                ->first()
            : null;

        /* ───────── Compute clock status ─────────
         * not_clocked_in  → no record yet
         * clocked_in      → record with clock_in but no clock_out
         * on_break        → clocked_in AND active break exists
         * clocked_out     → record with clock_out set
         */
        $clockStatus = 'not_clocked_in';
        if ($attendance) {
            if ($attendance->clock_out_at)       $clockStatus = 'clocked_out';
            elseif ($activeBreak)                $clockStatus = 'on_break';
            elseif ($attendance->clock_in_at)    $clockStatus = 'clocked_in';
        }

        /* ───────── Live worked minutes (excludes ongoing breaks) ───────── */
        $liveWorkedMinutes = 0;
        if ($attendance?->clock_in_at) {
            $end = $attendance->clock_out_at ?? $now;
            $totalMins = Carbon::parse($attendance->clock_in_at)->diffInMinutes($end);

            // Subtract completed break minutes
            $completedBreakMins = (int) AttendanceBreak::where('attendance_id', $attendance->id)
                ->whereNotNull('end_at')
                ->sum('duration_minutes');

            // Subtract ongoing break time (if any)
            $ongoingBreakMins = 0;
            if ($activeBreak) {
                $ongoingBreakMins = (int) Carbon::parse($activeBreak->start_at)->diffInMinutes($now);
            }

            $liveWorkedMinutes = max(0, $totalMins - $completedBreakMins - $ongoingBreakMins);
        }

        /* ───────── Today's shift (for progress bar / expected times) ───────── */
        $shift = $this->resolveShiftForDate($emp, $today);

        /* ───────── This month's stats ───────── */
        $monthAttendance = Attendance::where('employee_id', $emp->id)
            ->whereYear('work_date',  $today->year)
            ->whereMonth('work_date', $today->month)
            ->selectRaw("
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present'  THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent'   THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_days,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as leave_days,
                SUM(CASE WHEN is_late = 1         THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN is_early_out = 1    THEN 1 ELSE 0 END) as early_out_count,
                COALESCE(SUM(total_hours),    0) as total_hours,
                COALESCE(SUM(regular_hours),  0) as regular_hours,
                COALESCE(SUM(overtime_hours), 0) as overtime_hours,
                COALESCE(SUM(late_minutes),   0) as late_minutes
            ")
            ->first();

        // Working days elapsed (Mon–Fri up to today; tweak per company policy)
        $workingDaysElapsed = $this->countWorkingDays($today->copy()->startOfMonth(), $today);
        $workingDaysInMonth = $this->countWorkingDays($today->copy()->startOfMonth(), $today->copy()->endOfMonth());

        /* ───────── Leave balances (current year) ───────── */
        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('employee_id', $emp->id)
            ->where('year', $today->year)
            ->get()
            ->map(function ($b) {
                $available = (float) ($b->allocated + $b->accrued + $b->carried_over + $b->adjusted - $b->used - $b->pending);
                $total     = (float) ($b->allocated + $b->accrued + $b->carried_over + $b->adjusted);
                $usedPct   = $total > 0 ? min(100, round(($b->used / $total) * 100)) : 0;
                return (object) [
                    'name'      => $b->leaveType?->name      ?? 'Leave',
                    'color'     => $b->leaveType?->color     ?? '#6C7DF7',
                    'available' => round($available, 1),
                    'used'      => round((float) $b->used, 1),
                    'pending'   => round((float) $b->pending, 1),
                    'total'     => round($total, 1),
                    'used_pct'  => $usedPct,
                ];
            });

        /* ───────── Pending leave requests ───────── */
        $pendingLeavesCount = LeaveRequest::where('employee_id', $emp->id)
            ->where('status', 'pending')
            ->count();

        /* ───────── Upcoming leaves (approved, in the future) ───────── */
        $upcomingLeaves = LeaveRequest::with('leaveType')
            ->where('employee_id', $emp->id)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $today->toDateString())
            ->orderBy('start_date')
            ->limit(3)
            ->get();

        /* ───────── Last 7 days strip ───────── */
        $last7Dates = collect(range(6, 0))->map(fn ($i) => $today->copy()->subDays($i));
        $last7Records = Attendance::where('employee_id', $emp->id)
            ->whereIn('work_date', $last7Dates->map->toDateString())
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->work_date)->toDateString());

        $last7Holidays = Holiday::whereIn('date', $last7Dates->map->toDateString())
            ->get()->keyBy(fn ($h) => Carbon::parse($h->date)->toDateString());

        $last7 = $last7Dates->map(function ($d) use ($last7Records, $last7Holidays) {
            $key  = $d->toDateString();
            $att  = $last7Records->get($key);
            $hol  = $last7Holidays->get($key);
            $isWk = $d->isWeekend();

            // Resolve status keyword used by the view's dot color
            $dot = 'none';
            if ($att) {
                if     ($att->status === 'present' && $att->is_late) $dot = 'late';
                elseif ($att->status === 'present')                  $dot = 'present';
                elseif ($att->status === 'absent')                   $dot = 'absent';
                elseif ($att->status === 'on_leave')                 $dot = 'leave';
                elseif ($att->status === 'half_day')                 $dot = 'half';
                elseif ($att->status === 'holiday')                  $dot = 'holiday';
                else                                                 $dot = 'present';
            } elseif ($hol) {
                $dot = 'holiday';
            } elseif ($isWk) {
                $dot = 'weekend';
            }

            return (object) [
                'date'     => $d,
                'is_today' => $d->isToday(),
                'dot'      => $dot,
                'hours'    => $att?->total_hours ?? 0,
                'tooltip'  => $att
                    ? ucfirst(str_replace('_',' ', $att->status)) . ($att->total_hours ? ' · ' . $att->total_hours.'h' : '')
                    : ($hol?->name ?? ($isWk ? 'Weekend' : 'No record')),
            ];
        });

        /* ───────── Latest announcements ───────── */
        $announcements = $this->fetchAnnouncements($user->id);

        /* ───────── My upcoming tasks ───────── */
        $upcomingTasks = $this->fetchUpcomingTasks($emp->id);

        /* ───────── Birthdays this week (excluding myself) ───────── */
        $birthdaysThisWeek = $this->fetchBirthdaysThisWeek($emp->id);

        /* ───────── Quote-of-the-day (lightweight, just a vibe item) ───────── */
        $quotes = [
            'Small steps every day.',
            'Discipline equals freedom.',
            'Progress over perfection.',
            'Work hard in silence.',
            'Stay focused, stay humble.',
            'Done is better than perfect.',
        ];
        $quote = $quotes[$today->dayOfYear % count($quotes)];

        return view('employee.dashboard', [
            'tenantSlug'         => $tenant,
            'noEmployee'         => false,
            'emp'                => $emp,
            'attendance'         => $attendance,
            'activeBreak'        => $activeBreak,
            'clockStatus'        => $clockStatus,
            'liveWorkedMinutes'  => $liveWorkedMinutes,
            'shift'              => $shift,
            'monthStats'         => $monthAttendance,
            'workingDaysElapsed' => $workingDaysElapsed,
            'workingDaysInMonth' => $workingDaysInMonth,
            'leaveBalances'      => $leaveBalances,
            'pendingLeavesCount' => $pendingLeavesCount,
            'upcomingLeaves'     => $upcomingLeaves,
            'last7'              => $last7,
            'announcements'      => $announcements,
            'upcomingTasks'      => $upcomingTasks,
            'birthdaysThisWeek'  => $birthdaysThisWeek,
            'quote'              => $quote,
        ]);
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    /** Resolve the active shift assignment for a given date. */
    protected function resolveShiftForDate(Employee $emp, Carbon $date): ?object
    {
        $assignment = ShiftAssignment::with('shift')
            ->where('employee_id', $emp->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->latest('start_date')
            ->first();

        if (! $assignment?->shift) return null;

        $s = $assignment->shift;

        $start = Carbon::parse($date->toDateString().' '.$s->start_time);
        $end   = Carbon::parse($date->toDateString().' '.$s->end_time);
        if ($end->lte($start)) $end->addDay(); // overnight shift

        return (object) [
            'name'       => $s->name,
            'start_time' => $s->start_time,
            'end_time'   => $s->end_time,
            'start_at'   => $start,
            'end_at'     => $end,
            'label'      => $start->format('h:i A').' – '.$end->format('h:i A'),
        ];
    }

    /** Count Mon–Fri working days between two dates inclusive. */
    protected function countWorkingDays(Carbon $from, Carbon $to): int
    {
        $count = 0;
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if (! $d->isWeekend()) $count++;
        }
        return $count;
    }

    /** Latest 4 announcements with "unread" flag for the current user. */
    protected function fetchAnnouncements(int $userId)
    {
        if (! Schema::connection('tenant')->hasTable('announcements')) {
            return collect();
        }

        $list = Announcement::query()
            ->when(Schema::connection('tenant')->hasColumn('announcements', 'is_published'),
                fn ($q) => $q->where('is_published', true))
            ->when(Schema::connection('tenant')->hasColumn('announcements', 'published_at'),
                fn ($q) => $q->where(fn ($qq) =>
                    $qq->whereNull('published_at')->orWhere('published_at', '<=', now())
                ))
            ->latest('id')
            ->limit(4)
            ->get();

        $readIds = collect();
        if (Schema::connection('tenant')->hasTable('announcement_reads')) {
            $readIds = AnnouncementRead::where('user_id', $userId)
                ->whereIn('announcement_id', $list->pluck('id'))
                ->pluck('announcement_id');
        }

        return $list->map(function ($a) use ($readIds) {
            return (object) [
                'id'      => $a->id,
                'title'   => $a->title ?? 'Announcement',
                'excerpt' => $this->excerpt($a->body ?? $a->content ?? '', 120),
                'when'    => Carbon::parse($a->published_at ?? $a->created_at)->diffForHumans(),
                'unread'  => ! $readIds->contains($a->id),
            ];
        });
    }

    /** Top 5 upcoming tasks assigned to me, sorted by due date. */
    protected function fetchUpcomingTasks(int $empId)
    {
        if (! Schema::connection('tenant')->hasTable('task_assignees')) {
            return collect();
        }

        $taskIds = TaskAssignee::where('employee_id', $empId)->pluck('task_id');
        if ($taskIds->isEmpty()) return collect();

        return Task::query()
            ->whereIn('id', $taskIds)
            ->when(Schema::connection('tenant')->hasColumn('tasks', 'status'),
                fn ($q) => $q->whereNotIn('status', ['done', 'completed', 'archived', 'cancelled']))
            ->when(Schema::connection('tenant')->hasColumn('tasks', 'due_date'),
                fn ($q) => $q->orderByRaw('due_date IS NULL, due_date ASC'),
                fn ($q) => $q->latest('id'))
            ->limit(5)
            ->get()
            ->map(function ($t) {
                $due = $t->due_date ? Carbon::parse($t->due_date) : null;
                return (object) [
                    'id'       => $t->id,
                    'title'    => $t->title ?? $t->name ?? 'Task #'.$t->id,
                    'priority' => $t->priority ?? 'medium',
                    'status'   => $t->status ?? 'todo',
                    'due'      => $due,
                    'due_label'=> $due ? $due->diffForHumans() : null,
                    'overdue'  => $due ? $due->lt(now()) : false,
                ];
            });
    }

    /** Employees whose birthday falls within the next 7 days (excluding self). */
    protected function fetchBirthdaysThisWeek(int $myEmpId)
    {
        if (! Schema::connection('tenant')->hasColumn('employees', 'date_of_birth')) {
            return collect();
        }

        $todayMonth = now()->month;
        $todayDay   = now()->day;
        $endDate    = now()->addDays(7);
        $endMonth   = $endDate->month;
        $endDay     = $endDate->day;

        // Handles year wrap (e.g., Dec 28 – Jan 4)
        $query = Employee::query()
            ->whereNotNull('date_of_birth')
            ->where('id', '!=', $myEmpId)
            ->where('employment_status', 'active');

        if ($todayMonth === $endMonth) {
            $query->whereRaw('MONTH(date_of_birth) = ?', [$todayMonth])
                  ->whereRaw('DAY(date_of_birth) BETWEEN ? AND ?', [$todayDay, $endDay]);
        } else {
            $query->where(function ($q) use ($todayMonth, $todayDay, $endMonth, $endDay) {
                $q->where(function ($qq) use ($todayMonth, $todayDay) {
                    $qq->whereRaw('MONTH(date_of_birth) = ?', [$todayMonth])
                       ->whereRaw('DAY(date_of_birth) >= ?', [$todayDay]);
                })->orWhere(function ($qq) use ($endMonth, $endDay) {
                    $qq->whereRaw('MONTH(date_of_birth) = ?', [$endMonth])
                       ->whereRaw('DAY(date_of_birth) <= ?', [$endDay]);
                });
            });
        }

        return $query
            ->orderByRaw('MONTH(date_of_birth), DAY(date_of_birth)')
            ->limit(6)
            ->get(['id', 'first_name', 'last_name', 'avatar', 'date_of_birth', 'department_id'])
            ->map(function ($e) {
                $dob   = Carbon::parse($e->date_of_birth);
                $bday  = Carbon::createFromDate(now()->year, $dob->month, $dob->day);
                if ($bday->lt(now()->startOfDay())) $bday->addYear();
                return (object) [
                    'name'   => trim($e->first_name.' '.$e->last_name),
                    'avatar' => $e->avatar_url,
                    'when'   => $bday->isToday() ? 'Today' : $bday->diffForHumans(),
                    'date'   => $bday->format('M j'),
                ];
            });
    }

    /** Plain-text excerpt (strips HTML safely). */
    protected function excerpt(?string $html, int $len = 120): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));
        return strlen($text) <= $len ? $text : rtrim(substr($text, 0, $len)).'…';
    }

    /* ================================================================
     | Placeholders kept from Phase 1 (used by unbuilt route stubs)
     |================================================================*/
    public function comingSoon(Request $request, string $tenant)
    {
        return view('employee.coming-soon', [
            'tenantSlug' => $tenant,
            'feature'    => $this->guessFeatureName($request),
        ]);
    }

    public function comingSoonJson(Request $request, string $tenant)
    {
        return response()->json([
            'items'        => [],
            'unread_count' => 0,
            'message'      => 'Feature not yet available.',
        ]);
    }

    protected function guessFeatureName(Request $request): string
    {
        $name = $request->route()?->getName() ?? 'feature';
        $name = str_replace('employee.', '', $name);
        $parts = array_map(
            fn ($p) => ucwords(str_replace(['-', '_', '.'], ' ', $p)),
            explode('.', $name)
        );
        return implode(' · ', $parts);
    }
}