<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\LocationResource;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\AttendanceBreak;
use App\Models\Tenant\Holiday;
use App\Models\Tenant\Location;
use App\Models\Tenant\QrCode;
use App\Models\Tenant\Setting;
use App\Models\Tenant\ShiftAssignment;
use App\Services\AttendanceService;
use App\Services\GeofenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * JSON mirror of Employee/AttendanceController — same validation rules,
 * same AttendanceService/GeofenceService, no Blade/redirect branches.
 */
class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected GeofenceService $geofence,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $month = $request->get('month')
            ? Carbon::parse($request->get('month').'-01')
            : $emp->localToday()->startOfMonth();

        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $records = Attendance::with(['location', 'breaks'])
            ->where('employee_id', $emp->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->work_date)->toDateString());

        $holidays = Holiday::visibleTo($this->assignedLocationIds($emp)->all())
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($h) => Carbon::parse($h->date)->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $att = $records->get($key);
            $hol = $holidays->get($key);

            $days[] = [
                'date' => $key,
                'is_weekend' => $d->isWeekend(),
                'holiday_name' => $hol?->name,
                'status_key' => $this->statusKey($att, $hol, $d),
                'attendance' => $att ? new AttendanceResource($att) : null,
            ];
        }

        $summary = [
            'present_days' => $records->where('status', 'present')->count(),
            'absent_days' => $records->where('status', 'absent')->count(),
            'half_days' => $records->where('status', 'half_day')->count(),
            'leave_days' => $records->where('status', 'on_leave')->count(),
            'late_count' => $records->where('is_late', true)->count(),
            'early_out' => $records->where('is_early_out', true)->count(),
            'total_hours' => round((float) $records->sum('total_hours'), 2),
            'regular_hours' => round((float) $records->sum('regular_hours'), 2),
            'overtime_hours' => round((float) $records->sum('overtime_hours'), 2),
            'break_hours' => round((float) $records->sum('break_hours'), 2),
        ];

        $today = $emp->localToday();
        $todayRec = $records->get($today->toDateString());
        $activeBreak = $todayRec
            ? AttendanceBreak::where('attendance_id', $todayRec->id)->whereNull('end_at')->first()
            : null;
        $todayShift = $this->resolveShiftForDate($emp, $today);

        return response()->json([
            'month' => $month->format('Y-m'),
            'days' => $days,
            'summary' => $summary,
            'today' => [
                'clock_status' => $this->clockStatusFor($todayRec, $activeBreak),
                'attendance' => $todayRec ? new AttendanceResource($todayRec) : null,
                'shift' => $todayShift,
            ],
            'assigned_locations' => LocationResource::collection($this->resolveAssignedLocations($emp)),
            'employment_mode' => $emp->employment_mode,
        ]);
    }

    public function day(Request $request, string $date): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        try {
            $d = Carbon::parse($date);
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid date.'], 400);
        }

        $att = Attendance::with(['location', 'breaks' => fn ($q) => $q->orderBy('start_at')])
            ->where('employee_id', $emp->id)
            ->where('work_date', $d->toDateString())
            ->first();

        $hol = Holiday::visibleTo($this->assignedLocationIds($emp)->all())->whereDate('date', $d->toDateString())->first();
        $shift = $this->resolveShiftForDate($emp, $d);

        return response()->json([
            'date' => $d->toDateString(),
            'attendance' => $att ? new AttendanceResource($att) : null,
            'holiday_name' => $hol?->name,
            'shift' => $shift,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        try {
            $from = $request->get('from') ? Carbon::parse($request->get('from')) : $emp->localToday()->subDays(29);
            $to = $request->get('to') ? Carbon::parse($request->get('to')) : $emp->localToday();
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid date.'], 400);
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $records = Attendance::with(['location', 'breaks'])
            ->where('employee_id', $emp->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('work_date')
            ->paginate(30);

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'records' => AttendanceResource::collection($records->items()),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $att = Attendance::where('employee_id', $emp->id)
            ->where('work_date', $emp->localToday()->toDateString())
            ->first();
        $active = $att
            ? AttendanceBreak::where('attendance_id', $att->id)->whereNull('end_at')->first()
            : null;

        $worked = 0;
        if ($att?->clock_in_at) {
            $end = $att->clock_out_at ?? now();
            $total = Carbon::parse($att->clock_in_at)->diffInMinutes($end);
            $done = (int) AttendanceBreak::where('attendance_id', $att->id)
                ->whereNotNull('end_at')->sum('duration_minutes');
            $ongoing = $active ? (int) Carbon::parse($active->start_at)->diffInMinutes(now()) : 0;
            $worked = max(0, $total - $done - $ongoing);
        }

        return response()->json([
            'status' => $this->clockStatusFor($att, $active),
            'clock_in_at' => $att?->clock_in_at?->toIso8601String(),
            'clock_out_at' => $att?->clock_out_at?->toIso8601String(),
            'is_late' => (bool) $att?->is_late,
            'late_minutes' => (int) $att?->late_minutes,
            'worked_minutes' => $worked,
            'break_started' => $active?->start_at?->toIso8601String(),
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $data = $request->validate([
            'source' => 'required|in:web,qr,mobile',
            'location_id' => 'nullable|integer|exists:locations,id',
            'qr_token' => 'nullable|string',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:255',
        ]);

        $resolved = $this->resolveClockInTarget($data, $emp);
        if (isset($resolved['error'])) {
            return $this->fail($resolved['error']);
        }
        $location = $resolved['location'];
        $qr       = $resolved['qrCode'];

        $lat = (float) ($data['lat'] ?? $location?->latitude ?? 0);
        $lng = (float) ($data['lng'] ?? $location?->longitude ?? 0);

        $result = $this->attendance->clockIn(
            employee: $emp,
            location: $location,
            lat: $lat,
            lng: $lng,
            source: $data['source'],
            qrCode: $qr,
        );

        if (! $result['success']) {
            return $this->fail($result['message'], 422, $result);
        }

        if ($notes = ($data['notes'] ?? null)) {
            $result['attendance']->update(['notes' => $notes]);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'attendance' => new AttendanceResource($result['attendance']->fresh(['location', 'breaks'])),
        ]);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $data = $request->validate([
            'source' => 'required|in:web,qr,mobile',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:255',
        ]);

        $lat = (float) ($data['lat'] ?? 0);
        $lng = (float) ($data['lng'] ?? 0);

        $result = $this->attendance->clockOut(
            employee: $emp,
            lat: $lat,
            lng: $lng,
            source: $data['source'],
        );

        if (! $result['success']) {
            return $this->fail($result['message'], 422, $result);
        }

        if ($notes = ($data['notes'] ?? null)) {
            $att = $result['attendance'];
            $att->update(['notes' => trim(($att->notes ?? '').' '.$notes)]);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'attendance' => new AttendanceResource($result['attendance']->fresh(['location', 'breaks'])),
        ]);
    }

    public function startBreak(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $data = $request->validate([
            'break_type' => 'nullable|string|in:lunch,tea,personal,other',
            'notes' => 'nullable|string|max:255',
        ]);

        $att = Attendance::where('employee_id', $emp->id)
            ->where('work_date', $emp->localToday()->toDateString())
            ->first();

        if (! $att || ! $att->clock_in_at) {
            return $this->fail('You must clock in before taking a break.');
        }
        if ($att->clock_out_at) {
            return $this->fail('You have already clocked out today.');
        }

        $existing = AttendanceBreak::where('attendance_id', $att->id)->whereNull('end_at')->first();
        if ($existing) {
            return $this->fail('A break is already in progress.');
        }

        $break = AttendanceBreak::create([
            'attendance_id' => $att->id,
            'start_at' => Carbon::now(),
            'duration_minutes' => 0,
            'break_type' => $data['break_type'] ?? 'tea',
            'is_paid' => false,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Break started.', 'break' => $break]);
    }

    public function endBreak(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $att = Attendance::where('employee_id', $emp->id)
            ->where('work_date', $emp->localToday()->toDateString())
            ->first();

        if (! $att) return $this->fail('No attendance record for today.');

        $break = AttendanceBreak::where('attendance_id', $att->id)
            ->whereNull('end_at')
            ->latest('id')
            ->first();

        if (! $break) return $this->fail('No active break to end.');

        $end = Carbon::now();
        $mins = max(0, Carbon::parse($break->start_at)->diffInMinutes($end));
        $break->update(['end_at' => $end, 'duration_minutes' => $mins]);

        return response()->json(['success' => true, 'message' => "Break ended ({$mins} min)."]);
    }

    public function export(Request $request): StreamedResponse
    {
        $emp = $this->employeeOrFail($request);

        $month = $request->get('month')
            ? Carbon::parse($request->get('month').'-01')
            : $emp->localToday()->startOfMonth();

        $records = Attendance::with('location')
            ->where('employee_id', $emp->id)
            ->whereBetween('work_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('work_date')
            ->get();

        $filename = sprintf(
            'attendance-%s-%s.csv',
            Str::slug($emp->employee_code ?: $emp->id),
            $month->format('Y-m')
        );

        return response()->streamDownload(function () use ($records) {
            $h = fopen('php://output', 'w');
            fputcsv($h, [
                'Date', 'Day', 'Status', 'Clock In', 'Clock Out',
                'Hours', 'Regular', 'Overtime', 'Break (h)',
                'Late?', 'Late (min)', 'Early Out?', 'Location', 'Source',
            ]);

            foreach ($records as $r) {
                $date = Carbon::parse($r->work_date);
                fputcsv($h, [
                    $date->toDateString(),
                    $date->format('D'),
                    ucfirst(str_replace('_', ' ', $r->status ?? '')),
                    $r->clock_in_at ? Carbon::parse($r->clock_in_at)->format('H:i') : '',
                    $r->clock_out_at ? Carbon::parse($r->clock_out_at)->format('H:i') : '',
                    number_format((float) $r->total_hours, 2),
                    number_format((float) $r->regular_hours, 2),
                    number_format((float) $r->overtime_hours, 2),
                    number_format((float) $r->break_hours, 2),
                    $r->is_late ? 'Yes' : 'No',
                    (int) $r->late_minutes,
                    $r->is_early_out ? 'Yes' : 'No',
                    $r->location?->name ?? '',
                    $r->source ?? '',
                ]);
            }

            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    protected function employeeOrFail(Request $request)
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return $emp;
    }

    protected function fail(string $msg, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $msg] + $extra, $status);
    }

    /**
     * Resolve what a clock-in should be checked against.
     *
     * - Scanning a QR always resolves that QR's own location.
     * - Otherwise (web/direct), resolve the assigned/selected location if any. If that location has an
     *   active QR code assigned, a scan is required — direct clock-in is rejected so the QR requirement
     *   can't be bypassed by just choosing the location from the list instead of scanning it.
     * - No assigned location and none selected → unrestricted direct clock-in (no geofence).
     *
     * @return array{location: ?Location, qrCode: ?QrCode, error?: string}
     */
    protected function resolveClockInTarget(array $data, $emp): array
    {
        if (! empty($data['qr_token'])) {
            $token = $this->extractQrToken($data['qr_token']);

            $qr = QrCode::with('location')->where('token', $token)->first();
            if (! $qr) return ['location' => null, 'qrCode' => null, 'error' => 'Invalid QR code.'];
            if (isset($qr->is_active) && ! $qr->is_active) {
                return ['location' => null, 'qrCode' => null, 'error' => 'This QR code is inactive.'];
            }

            return ['location' => $qr->location, 'qrCode' => $qr];
        }

        $locId = $data['location_id'] ?? $emp->location_id ?? null;

        if (! $locId) {
            return ['location' => null, 'qrCode' => null];
        }

        $assignedIds = $this->assignedLocationIds($emp);
        if ($assignedIds->isNotEmpty() && ! $assignedIds->contains((int) $locId)) {
            return ['location' => null, 'qrCode' => null, 'error' => 'You are not assigned to that location.'];
        }

        $location = Location::find($locId);
        if (! $location) {
            return ['location' => null, 'qrCode' => null, 'error' => 'Selected location was not found.'];
        }

        $hasActiveQr = QrCode::where('location_id', $location->id)->where('is_active', true)->exists();
        if ($hasActiveQr && ! $emp->skipsGeofence()) {
            return ['location' => $location, 'qrCode' => null, 'error' => 'This location requires a QR code scan to clock in.'];
        }

        return ['location' => $location, 'qrCode' => null];
    }

    /**
     * QR codes encode a full scan URL (e.g. https://.../api/v1/attendance/scan/{token}). Accept either
     * that full URL or a raw token and resolve down to just the token segment.
     */
    protected function extractQrToken(string $raw): string
    {
        $trimmed = rtrim(trim($raw), '/');
        $segments = explode('/', $trimmed);

        return end($segments) ?: $trimmed;
    }

    protected function resolveShiftForDate($emp, Carbon $date): ?array
    {
        $assignment = ShiftAssignment::with('shift')
            ->where('employee_id', $emp->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date->toDateString()))
            ->latest('start_date')
            ->first();

        if (! $assignment?->shift) return null;
        $s = $assignment->shift;

        // Explicit timezone so this always agrees with the timezone
        // AttendanceService::enforceShiftWindow() uses for the same shift,
        // rather than depending on whatever the ambient PHP default happens
        // to be at this point in the request.
        $tz = $emp->attendanceTimezone();
        $start = Carbon::parse($date->toDateString().' '.$s->start_time, $tz);
        $end = Carbon::parse($date->toDateString().' '.$s->end_time, $tz);
        if ($end->lte($start)) $end->addDay();

        return [
            'name' => $s->name,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'label' => $start->format('h:i A').' - '.$end->format('h:i A'),
        ];
    }

    /**
     * An employee's assigned locations — their primary `location_id` plus any additional ones from
     * `employee_locations` (e.g. an employee splitting time across two branches). Returning only the
     * employee's own locations (never every company location) is what lets the mobile app skip the
     * picker entirely when there's exactly one, and show a real picker only when there's genuinely
     * more than one to choose from.
     */
    protected function resolveAssignedLocations($emp)
    {
        $ids = $this->assignedLocationIds($emp);

        if ($ids->isEmpty()) {
            return collect();
        }

        return Location::whereIn('id', $ids)
            ->withCount(['qrCodes as active_qr_count' => fn ($qq) => $qq->where('is_active', true)])
            ->orderByRaw('id != ?', [$emp->location_id])
            ->get();
    }

    /** IDs of every location this employee is assigned to (primary `location_id` + `employee_locations` pivot). */
    protected function assignedLocationIds($emp)
    {
        return $emp->locations()->pluck('locations.id')
            ->when($emp->location_id, fn ($c) => $c->push($emp->location_id))
            ->unique()
            ->values();
    }

    protected function statusKey($att, $hol, Carbon $d): string
    {
        if ($att) {
            if ($att->status === 'present' && $att->is_late) return 'late';
            if ($att->status === 'present') return 'present';
            if ($att->status === 'absent') return 'absent';
            if ($att->status === 'on_leave') return 'leave';
            if ($att->status === 'half_day') return 'half';
            if ($att->status === 'holiday') return 'holiday';

            return 'present';
        }
        if ($hol) return 'holiday';
        if ($d->isWeekend()) return 'weekend';
        if ($d->isFuture()) return 'future';

        return 'none';
    }

    protected function clockStatusFor($att, $break): string
    {
        if (! $att) return 'not_clocked_in';
        if ($att->clock_out_at) return 'clocked_out';
        if ($break) return 'on_break';
        if ($att->clock_in_at) return 'clocked_in';

        return 'not_clocked_in';
    }
}
