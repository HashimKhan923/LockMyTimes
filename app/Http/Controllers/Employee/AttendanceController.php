<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected GeofenceService   $geofence,
    ) {}

    /* ================================================================
     | INDEX — Calendar + List view
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $view  = $request->get('view', 'calendar'); // 'calendar' | 'list'
        $month = $request->get('month')
            ? Carbon::parse($request->get('month').'-01')
            : Carbon::today()->startOfMonth();

        $from = $month->copy()->startOfMonth();
        $to   = $month->copy()->endOfMonth();

        /* ───────── Pull the month's attendance keyed by date string ───────── */
        $records = Attendance::with(['location', 'breaks'])
            ->where('employee_id', $emp->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->work_date)->toDateString());

        /* ───────── Holidays in this range ───────── */
        $holidays = Holiday::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($h) => Carbon::parse($h->date)->toDateString());

        /* ───────── Build calendar grid cells (Mon-start by default) ───────── */
        $weekStartsMonday = (bool) Setting::get('attendance.week_starts_monday', true);
        $gridStart = $from->copy()->startOfWeek($weekStartsMonday ? Carbon::MONDAY : Carbon::SUNDAY);
        $gridEnd   = $to->copy()->endOfWeek($weekStartsMonday ? Carbon::SUNDAY : Carbon::SATURDAY);

        $calendarCells = collect();
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $key = $d->toDateString();
            $att = $records->get($key);
            $hol = $holidays->get($key);

            $calendarCells->push((object) [
                'date'        => $d->copy(),
                'in_month'    => $d->month === $month->month,
                'is_today'    => $d->isToday(),
                'is_weekend'  => $d->isWeekend(),
                'holiday'     => $hol,
                'attendance'  => $att,
                'status_key'  => $this->statusKey($att, $hol, $d),
            ]);
        }

        /* ───────── Month summary stats ───────── */
        $summary = (object) [
            'present_days'   => $records->where('status', 'present')->count(),
            'absent_days'    => $records->where('status', 'absent')->count(),
            'half_days'      => $records->where('status', 'half_day')->count(),
            'leave_days'     => $records->where('status', 'on_leave')->count(),
            'late_count'     => $records->where('is_late', true)->count(),
            'early_out'      => $records->where('is_early_out', true)->count(),
            'total_hours'    => round((float) $records->sum('total_hours'), 2),
            'regular_hours'  => round((float) $records->sum('regular_hours'), 2),
            'overtime_hours' => round((float) $records->sum('overtime_hours'), 2),
            'break_hours'    => round((float) $records->sum('break_hours'), 2),
        ];

        /* ───────── Today's snapshot for the clock-in/out card ───────── */
        $today        = Carbon::today();
        $todayRec     = $records->get($today->toDateString());
        $activeBreak  = $todayRec
            ? AttendanceBreak::where('attendance_id', $todayRec->id)->whereNull('end_at')->first()
            : null;
        $clockStatus  = $this->clockStatusFor($todayRec, $activeBreak);
        $todayShift   = $this->resolveShiftForDate($emp, $today);
        $assignedLocs = $this->resolveAssignedLocations($emp);

        return view('employee.attendance.index', [
            'tenantSlug'    => $tenant,
            'emp'           => $emp,
            'view'          => $view,
            'month'         => $month,
            'calendarCells' => $calendarCells,
            'records'       => $records,
            'holidays'      => $holidays,
            'summary'       => $summary,
            'todayRec'      => $todayRec,
            'activeBreak'   => $activeBreak,
            'clockStatus'   => $clockStatus,
            'todayShift'    => $todayShift,
            'assignedLocs'  => $assignedLocs,
            'weekStartsMon' => $weekStartsMonday,
        ]);
    }

    /* ================================================================
     | DAY DETAIL (JSON for drawer)
     |================================================================*/
    public function day(string $tenant, string $date)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        try {
            $d = Carbon::parse($date);
        } catch (\Throwable) {
            abort(400, 'Invalid date.');
        }

        $att = Attendance::with(['location', 'breaks' => fn ($q) => $q->orderBy('start_at')])
            ->where('employee_id', $emp->id)
            ->where('work_date', $d->toDateString())
            ->first();

        $hol   = Holiday::whereDate('date', $d->toDateString())->first();
        $shift = $this->resolveShiftForDate($emp, $d);

        return response()->json([
            'html' => view('employee.attendance._day_drawer', [
                'tenantSlug' => $tenant,
                'date'       => $d,
                'att'        => $att,
                'holiday'    => $hol,
                'shift'      => $shift,
            ])->render(),
        ]);
    }

    /* ================================================================
     | CLOCK IN
     |================================================================*/
    public function clockIn(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'source'    => 'required|in:web,qr',
            'location_id'=> 'nullable|integer|exists:locations,id',
            'qr_token'  => 'nullable|string',
            'lat'       => 'nullable|numeric|between:-90,90',
            'lng'       => 'nullable|numeric|between:-180,180',
            'selfie'    => 'nullable|string', // base64 dataURL
            'notes'     => 'nullable|string|max:255',
        ]);

        // Resolve the QR code to clock against.
        $qr = $this->resolveQrCode($data, $emp);
        if (! $qr instanceof QrCode) {
            return $this->fail($qr['message']);
        }

        $lat = (float) ($data['lat'] ?? $qr->location->latitude ?? 0);
        $lng = (float) ($data['lng'] ?? $qr->location->longitude ?? 0);

        $selfiePath = $this->maybeStoreSelfie($data['selfie'] ?? null, $emp->id, 'in');

        $result = $this->attendance->clockIn(
            employee: $emp,
            qrCode:   $qr,
            lat:      $lat,
            lng:      $lng,
            source:   $data['source'],
            selfie:   $selfiePath
        );

        if (! $result['success']) {
            // Clean up the orphan selfie if the service rejected the clock-in
            if ($selfiePath) Storage::disk('public')->delete($selfiePath);
            return $this->fail($result['message'], 422, $result);
        }

        if ($notes = ($data['notes'] ?? null)) {
            $result['attendance']->update(['notes' => $notes]);
        }

        return $request->wantsJson()
            ? response()->json($result + ['success' => true])
            : back()->with('success', $result['message']);
    }

    /* ================================================================
     | CLOCK OUT
     |================================================================*/
    public function clockOut(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'source'  => 'required|in:web,qr',
            'lat'     => 'nullable|numeric|between:-90,90',
            'lng'     => 'nullable|numeric|between:-180,180',
            'selfie'  => 'nullable|string',
            'notes'   => 'nullable|string|max:255',
        ]);

        $lat = (float) ($data['lat'] ?? 0);
        $lng = (float) ($data['lng'] ?? 0);

        $selfiePath = $this->maybeStoreSelfie($data['selfie'] ?? null, $emp->id, 'out');

        $result = $this->attendance->clockOut(
            employee: $emp,
            lat:      $lat,
            lng:      $lng,
            source:   $data['source'],
            selfie:   $selfiePath
        );

        if (! $result['success']) {
            if ($selfiePath) Storage::disk('public')->delete($selfiePath);
            return $this->fail($result['message'], 422, $result);
        }

        if ($notes = ($data['notes'] ?? null)) {
            $att = $result['attendance'];
            $att->update(['notes' => trim(($att->notes ?? '').' '.$notes)]);
        }

        return $request->wantsJson()
            ? response()->json($result + ['success' => true])
            : back()->with('success', $result['message']);
    }

    /* ================================================================
     | BREAKS — start / end
     |================================================================*/
    public function startBreak(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'break_type' => 'nullable|string|in:lunch,tea,personal,other',
            'notes'      => 'nullable|string|max:255',
        ]);

        $att = Attendance::where('employee_id', $emp->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();

        if (! $att || ! $att->clock_in_at) {
            return $this->fail('You must clock in before taking a break.');
        }
        if ($att->clock_out_at) {
            return $this->fail('You have already clocked out today.');
        }

        $existing = AttendanceBreak::where('attendance_id', $att->id)
            ->whereNull('end_at')->first();
        if ($existing) {
            return $this->fail('A break is already in progress.');
        }

        $break = AttendanceBreak::create([
            'attendance_id'    => $att->id,
            'start_at'         => Carbon::now(),
            'duration_minutes' => 0,
            'break_type'       => $data['break_type'] ?? 'tea',
            'is_paid'          => false,
            'notes'            => $data['notes'] ?? null,
        ]);

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'Break started.', 'break' => $break])
            : back()->with('success', 'Break started.');
    }

    public function endBreak(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $att = Attendance::where('employee_id', $emp->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();

        if (! $att) return $this->fail('No attendance record for today.');

        $break = AttendanceBreak::where('attendance_id', $att->id)
            ->whereNull('end_at')
            ->latest('id')
            ->first();

        if (! $break) return $this->fail('No active break to end.');

        $end = Carbon::now();
        $mins = max(0, Carbon::parse($break->start_at)->diffInMinutes($end));
        $break->update([
            'end_at'           => $end,
            'duration_minutes' => $mins,
        ]);

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => "Break ended ({$mins} min)."])
            : back()->with('success', "Break ended ({$mins} min).");
    }

    /* ================================================================
     | STATUS (JSON poller — used by hero card)
     |================================================================*/
    public function status(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $att = Attendance::where('employee_id', $emp->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();
        $active = $att
            ? AttendanceBreak::where('attendance_id', $att->id)->whereNull('end_at')->first()
            : null;

        // Live worked minutes
        $worked = 0;
        if ($att?->clock_in_at) {
            $end   = $att->clock_out_at ?? now();
            $total = Carbon::parse($att->clock_in_at)->diffInMinutes($end);
            $done  = (int) AttendanceBreak::where('attendance_id', $att->id)
                ->whereNotNull('end_at')->sum('duration_minutes');
            $ongoing = $active
                ? (int) Carbon::parse($active->start_at)->diffInMinutes(now())
                : 0;
            $worked = max(0, $total - $done - $ongoing);
        }

        return response()->json([
            'status'        => $this->clockStatusFor($att, $active),
            'clock_in_at'   => $att?->clock_in_at?->toIso8601String(),
            'clock_out_at'  => $att?->clock_out_at?->toIso8601String(),
            'is_late'       => (bool) $att?->is_late,
            'late_minutes'  => (int)  $att?->late_minutes,
            'worked_minutes'=> $worked,
            'break_started' => $active?->start_at?->toIso8601String(),
        ]);
    }

    /* ================================================================
     | EXPORT — CSV for the visible month
     |================================================================*/
    public function export(string $tenant, Request $request): StreamedResponse
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $month = $request->get('month')
            ? Carbon::parse($request->get('month').'-01')
            : Carbon::today()->startOfMonth();

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
                    $r->clock_in_at  ? Carbon::parse($r->clock_in_at)->format('H:i')  : '',
                    $r->clock_out_at ? Carbon::parse($r->clock_out_at)->format('H:i') : '',
                    number_format((float) $r->total_hours,    2),
                    number_format((float) $r->regular_hours,  2),
                    number_format((float) $r->overtime_hours, 2),
                    number_format((float) $r->break_hours,    2),
                    $r->is_late ? 'Yes' : 'No',
                    (int) $r->late_minutes,
                    $r->is_early_out ? 'Yes' : 'No',
                    $r->location?->name ?? '',
                    $r->source ?? '',
                ]);
            }

            fclose($h);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    protected function fail(string $msg, int $status = 422, array $extra = [])
    {
        if (request()->wantsJson()) {
            return response()->json(['success' => false, 'message' => $msg] + $extra, $status);
        }
        return back()->with('error', $msg)->withInput();
    }

    /** Resolve the appropriate QR code to clock-in against. */
    protected function resolveQrCode(array $data, $emp): QrCode|array
    {
        // 1) QR token (scanning a printed/desktop code)
        if (! empty($data['qr_token'])) {
            $qr = QrCode::with('location')->where('token', $data['qr_token'])
                ->orWhere('code', $data['qr_token'])
                ->first();
            if (! $qr) return ['message' => 'Invalid QR code.'];
            if (isset($qr->is_active) && ! $qr->is_active) {
                return ['message' => 'This QR code is inactive.'];
            }
            return $qr;
        }

        // 2) Web clock-in → pick the QR for the assigned/selected location
        $locId = $data['location_id']
            ?? $emp->location_id
            ?? null;

        if (! $locId) {
            return ['message' => 'No location selected. Please choose one or scan a QR code.'];
        }

        $qr = QrCode::with('location')->where('location_id', $locId)
            ->when(\Illuminate\Support\Facades\Schema::connection('tenant')->hasColumn('qr_codes', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->latest('id')
            ->first();

        if (! $qr) {
            // Build a virtual QR-like object the service can accept? Safer: fail clean.
            return ['message' => 'No QR code is configured for this location. Ask your admin to generate one.'];
        }

        return $qr;
    }

    /** Save the selfie (base64 dataURL) to public storage; return relative path. */
    protected function maybeStoreSelfie(?string $dataUrl, int $empId, string $kind): ?string
    {
        if (! $dataUrl || ! Str::startsWith($dataUrl, 'data:image/')) return null;

        if (! preg_match('#^data:image/(jpeg|jpg|png|webp);base64,(.+)$#', $dataUrl, $m)) {
            return null;
        }
        $ext  = strtolower($m[1] === 'jpg' ? 'jpeg' : $m[1]);
        $bin  = base64_decode($m[2], true);
        if ($bin === false) return null;

        $path = sprintf(
            'attendance/%d/%s/%s.%s',
            $empId,
            Carbon::today()->format('Y-m'),
            $kind.'_'.now()->format('YmdHis').'_'.Str::random(6),
            $ext
        );
        Storage::disk('public')->put($path, $bin);
        return $path;
    }

    /** Best-effort shift resolution for a given date. */
    protected function resolveShiftForDate($emp, Carbon $date): ?object
    {
        $assignment = ShiftAssignment::with('shift')
            ->where('employee_id', $emp->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date->toDateString()))
            ->latest('start_date')
            ->first();

        if (! $assignment?->shift) return null;
        $s = $assignment->shift;

        $start = Carbon::parse($date->toDateString().' '.$s->start_time);
        $end   = Carbon::parse($date->toDateString().' '.$s->end_time);
        if ($end->lte($start)) $end->addDay();

        return (object) [
            'name'  => $s->name,
            'start' => $start,
            'end'   => $end,
            'label' => $start->format('h:i A').' – '.$end->format('h:i A'),
        ];
    }

    /** Locations available to clock from (assigned location + same-tenant active). */
    protected function resolveAssignedLocations($emp)
    {
        // Primary: employee's assigned location first
        $primary = $emp->location_id ? Location::where('id', $emp->location_id)->get() : collect();

        // Plus any other active locations the company has
        $others = Location::query()
            ->when(\Illuminate\Support\Facades\Schema::connection('tenant')->hasColumn('locations', 'is_active'),
                fn ($q) => $q->where('is_active', true))
            ->when($emp->location_id, fn ($q) => $q->where('id', '!=', $emp->location_id))
            ->orderBy('name')
            ->get();

        return $primary->concat($others)->values();
    }

    /** Compact status keyword for the calendar cell. */
    protected function statusKey($att, $hol, Carbon $d): string
    {
        if ($att) {
            if ($att->status === 'present' && $att->is_late) return 'late';
            if ($att->status === 'present')                   return 'present';
            if ($att->status === 'absent')                    return 'absent';
            if ($att->status === 'on_leave')                  return 'leave';
            if ($att->status === 'half_day')                  return 'half';
            if ($att->status === 'holiday')                   return 'holiday';
            return 'present';
        }
        if ($hol)         return 'holiday';
        if ($d->isWeekend())  return 'weekend';
        if ($d->isFuture())   return 'future';
        return 'none';
    }

    /** Map an attendance row + break to a status keyword used by the UI. */
    protected function clockStatusFor($att, $break): string
    {
        if (! $att)               return 'not_clocked_in';
        if ($att->clock_out_at)   return 'clocked_out';
        if ($break)               return 'on_break';
        if ($att->clock_in_at)    return 'clocked_in';
        return 'not_clocked_in';
    }
}