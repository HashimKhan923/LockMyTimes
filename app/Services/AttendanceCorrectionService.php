<?php

namespace App\Services;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AttendanceCorrectionService
{
    /**
     * Approve a correction request: create the attendance record for that
     * day if none exists, or merge into a partial one (e.g. clocked in but
     * never out) without clobbering the side that wasn't corrected.
     */
    public static function approve(AttendanceCorrectionRequest $correction, int $approverId): void
    {
        $employee = $correction->employee;
        $tz       = $employee->attendanceTimezone();
        $dateStr  = $correction->work_date->toDateString();

        $attendance = Attendance::firstOrNew([
            'employee_id' => $correction->employee_id,
            'work_date'   => $dateStr,
        ]);

        if ($correction->proposed_clock_in) {
            $attendance->clock_in_at = Carbon::parse($dateStr.' '.$correction->proposed_clock_in, $tz);
        }
        if ($correction->proposed_clock_out) {
            $attendance->clock_out_at = Carbon::parse($dateStr.' '.$correction->proposed_clock_out, $tz);
        }

        if ($attendance->clock_in_at && $attendance->clock_out_at) {
            $totalHours = round($attendance->clock_in_at->diffInMinutes($attendance->clock_out_at) / 60, 2);
            $attendance->total_hours    = $totalHours;
            $attendance->regular_hours  = min($totalHours, 8);
            $attendance->overtime_hours = max(0, $totalHours - 8);
        }

        $attendance->status          = 'present';
        $attendance->source          = 'manual';
        $attendance->is_manual_entry = true;
        $attendance->is_approved     = true;
        $attendance->approved_by     = $approverId;
        $attendance->notes = trim(($attendance->notes ? $attendance->notes."\n" : '')
            ."Correction {$correction->request_number}: {$correction->reason}");
        $attendance->save();

        $correction->update([
            'status'      => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }
}
