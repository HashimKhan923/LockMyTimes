<?php

namespace App\Services;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Employee;
use App\Models\Tenant\QrCode;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Shift;
use App\Models\Tenant\ShiftAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class AttendanceService
{
    public function __construct(
        protected GeofenceService $geofence
    ) {}

    /* ================================================================
     | CLOCK IN
     |================================================================*/
    public function clockIn(
        Employee $employee,
        QrCode   $qrCode,
        float    $lat,
        float    $lng,
        string   $source = 'qr',
        ?string  $selfie = null
    ): array {
        $today    = Carbon::today();
        $now      = Carbon::now();
        $location = $qrCode->location;

        // Already clocked in today?
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('work_date', $today->toDateString())
            ->first();

        if ($existing && $existing->clock_in_at) {
            return ['success' => false, 'message' => 'You have already clocked in today.'];
        }

        // Source gate — check if this clock-in method is allowed
        $allowMap = [
            'qr'     => Setting::get('attendance.allow_qr',     true),
            'web'    => Setting::get('attendance.allow_web',    true),
            'mobile' => Setting::get('attendance.allow_mobile', true),
        ];
        if (isset($allowMap[$source]) && ! $allowMap[$source]) {
            return ['success' => false, 'message' => ucfirst($source) . ' clock-in is currently disabled.'];
        }

        // Selfie requirement
        if (Setting::get('attendance.require_selfie', false) && ! $selfie) {
            return ['success' => false, 'message' => 'A selfie photo is required for clock-in.'];
        }

        // Geofence check
        $geo = $this->geofence->check($location, $lat, $lng);

        if (! $geo['valid'] && Setting::get('attendance.geofence_strict', true)) {
            return [
                'success'  => false,
                'message'  => "You are {$geo['distance']}m away from {$location->name}. You must be within {$geo['radius']}m to clock in.",
                'distance' => $geo['distance'],
            ];
        }

        // ── Shift window enforcement ─────────────────────────────────
        $shiftCheck = $this->enforceShiftWindow($employee, $today, $now);
        if ($shiftCheck !== null) {
            return $shiftCheck; // returns ['success'=>false, 'message'=>...]
        }

        // Determine if late
        $graceMinutes = (int) Setting::get('attendance.late_grace_minutes', 10);
        $isLate       = false;
        $lateMinutes  = 0;
        $shiftStart   = $this->getShiftTime($employee, $today, 'start');

        if ($shiftStart) {
            $expectedIn = $shiftStart->copy()->addMinutes($graceMinutes);
            if ($now->gt($expectedIn)) {
                $isLate      = true;
                $lateMinutes = (int) $now->diffInMinutes($shiftStart);
            }
        }

        // Create or update attendance record
        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $today->toDateString()],
            [
                'location_id'              => $location->id,
                'qr_code_id'               => $qrCode->id,
                'clock_in_at'              => $now,
                'clock_in_lat'             => $lat,
                'clock_in_lng'             => $lng,
                'clock_in_distance_meters' => $geo['distance'],
                'clock_in_selfie'          => $selfie,
                'clock_in_ip'              => Request::ip(),
                'user_agent'               => Request::userAgent(),
                'is_late'                  => $isLate,
                'late_minutes'             => $lateMinutes,
                'is_geofence_breach'       => ! $geo['valid'],
                'status'                   => 'present',
                'source'                   => $source,
            ]
        );

        // Increment scan count
        $qrCode->increment('scan_count');

        return [
            'success'    => true,
            'message'    => $isLate
                ? "Clocked in at {$now->format('h:i A')} (late by {$lateMinutes} mins)"
                : "Clocked in at {$now->format('h:i A')} ",
            'attendance' => $attendance,
            'is_late'    => $isLate,
            'distance'   => $geo['distance'],
        ];
    }

    /* ================================================================
     | CLOCK OUT
     |================================================================*/
    public function clockOut(
        Employee $employee,
        float    $lat,
        float    $lng,
        string   $source = 'qr',
        ?string  $selfie = null
    ): array {
        $today = Carbon::today();
        $now   = Carbon::now();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('work_date', $today->toDateString())
            ->whereNotNull('clock_in_at')
            ->first();

        if (! $attendance) {
            return ['success' => false, 'message' => 'No clock-in found for today.'];
        }

        if ($attendance->clock_out_at) {
            return ['success' => false, 'message' => 'You have already clocked out today.'];
        }

        // Calculate hours
        $clockIn    = Carbon::parse($attendance->clock_in_at);
        $totalMins  = $clockIn->diffInMinutes($now);
        $breakMins  = $this->calculateBreakMinutes($attendance);
        $workedMins = max(0, $totalMins - $breakMins);
        $totalHours = round($workedMins / 60, 2);

        // Overtime threshold from settings (default 8h/day)
        $dailyOtThreshold = (float) Setting::get('attendance.overtime_threshold', 8);
        $regularHours     = min($totalHours, $dailyOtThreshold);
        $overtimeHours    = max(0, $totalHours - $dailyOtThreshold);

        // Early-out check
        $isEarlyOut   = false;
        $earlyMinutes = 0;
        $shiftEnd     = $this->getShiftTime($employee, $today, 'end');

        if ($shiftEnd) {
            $graceEarlyMins = (int) Setting::get('attendance.early_out_grace_minutes', 10);
            $expectedOut    = $shiftEnd->copy()->subMinutes($graceEarlyMins);
            if ($now->lt($expectedOut)) {
                $isEarlyOut   = true;
                $earlyMinutes = (int) $now->diffInMinutes($shiftEnd);
            }
        }

        $attendance->update([
            'clock_out_at'              => $now,
            'clock_out_lat'             => $lat,
            'clock_out_lng'             => $lng,
            'clock_out_distance_meters' => 0,
            'clock_out_selfie'          => $selfie,
            'clock_out_ip'              => Request::ip(),
            'total_hours'               => $totalHours,
            'break_hours'               => round($breakMins / 60, 2),
            'regular_hours'             => $regularHours,
            'overtime_hours'            => $overtimeHours,
            'is_early_out'              => $isEarlyOut,
            'early_minutes'             => $earlyMinutes,
        ]);

        return [
            'success'     => true,
            'message'     => "Clocked out at {$now->format('h:i A')}. Total: {$totalHours}h",
            'attendance'  => $attendance->fresh(),
            'total_hours' => $totalHours,
        ];
    }

    /* ================================================================
     | Shift Window Enforcement
     |================================================================*/

    /**
     * Returns an error array if the employee is outside their shift window,
     * or null if clock-in is allowed.
     *
     * Rules:
     * 1. If no shift assigned allow (don't block unscheduled employees).
     * 2. If shift_window_strict is disabled in settings skip.
     *  3. Today must be a working day for the shift.
     *  4. Current time must be within [shift_start - early_buffer, shift_end].
     */
    private function enforceShiftWindow(Employee $employee, Carbon $date, Carbon $now): ?array
    {
        if (! Setting::get('attendance.shift_window_strict', true)) {
            return null;
        }

        $assignment = $this->getShiftAssignment($employee, $date);

        // No shift assigned — don't block
        if (! $assignment?->shift) {
            return null;
        }

        $shift = $assignment->shift;
        $tz    = $employee->location?->timezone ?? config('app.timezone');

        // 1. Working day check (0=Sun … 6=Sat, matches Carbon::dayOfWeek)
        $workingDays = is_array($shift->working_days)
            ? $shift->working_days
            : json_decode($shift->working_days ?? '[]', true);

        $todayDow = (string) $date->dayOfWeek;

        if (! in_array($todayDow, array_map('strval', $workingDays))) {
            return [
                'success' => false,
                'message' => "Today is not a scheduled working day for your shift ({$shift->name}). Clock-in is not allowed.",
            ];
        }

        // 2. Time window check
        $shiftStart  = Carbon::createFromFormat('H:i:s', $shift->start_time, $tz);
        $shiftEnd    = Carbon::createFromFormat('H:i:s', $shift->end_time,   $tz);
        $nowInTz     = $now->copy()->setTimezone($tz);

        // Handle overnight shifts (e.g. 22:00 06:00)
        if ($shift->crosses_midnight || $shiftEnd->lte($shiftStart)) {
            if ($nowInTz->lt($shiftStart)) {
                // We are in the early-morning portion — compare against yesterday's start
                $shiftEnd->addDay();
            } else {
                $shiftEnd->addDay();
            }
        }

        $earlyBuffer = (int) Setting::get('attendance.early_clockin_minutes', 30);
        $windowOpen  = $shiftStart->copy()->subMinutes($earlyBuffer);

        if ($nowInTz->lt($windowOpen)) {
            $minsUntilOpen = (int) $nowInTz->diffInMinutes($windowOpen);
            return [
                'success' => false,
                'message' => "Your shift ({$shift->name}) starts at {$shiftStart->format('h:i A')}. "
                           . "Clock-in opens {$earlyBuffer} minutes before. "
                           . "Please wait {$minsUntilOpen} more minute(s).",
            ];
        }

        if ($nowInTz->gt($shiftEnd)) {
            return [
                'success' => false,
                'message' => "Your shift ({$shift->name}) ended at {$shiftEnd->format('h:i A')}. "
                           . "Clock-in is no longer available for this shift.",
            ];
        }

        return null;
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    private function getShiftAssignment(Employee $employee, Carbon $date): ?ShiftAssignment
    {
        return ShiftAssignment::where('employee_id', $employee->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $date->toDateString()))
            ->with('shift')
            ->latest('start_date')
            ->first();
    }

    private function getShiftTime(Employee $employee, Carbon $date, string $which): ?Carbon
    {
        $assignment = $this->getShiftAssignment($employee, $date);

        if (! $assignment?->shift) return null;

        $field = $which === 'start' ? 'start_time' : 'end_time';
        $tz    = $employee->location?->timezone ?? config('app.timezone');

        return Carbon::createFromFormat('H:i:s', $assignment->shift->{$field}, $tz);
    }

    private function calculateBreakMinutes(Attendance $attendance): int
    {
        return (int) $attendance->breaks()
            ->whereNotNull('end_at')
            ->sum('duration_minutes');
    }
}
