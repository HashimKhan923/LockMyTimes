<?php

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * attendance.overtime_threshold was seeded as '40' (a weekly FLSA figure)
     * but AttendanceService::clockOut() treats it as a *daily* threshold
     * (matching its own Settings UI label "Daily Overtime Threshold" and the
     * `?? 8` fallback used everywhere else). Since no single day reaches 40
     * hours, every normal clock-out silently computed 0 overtime regardless
     * of how long the shift actually ran.
     *
     * Fix: correct the setting for tenants still sitting on the untouched
     * '40' default, then recompute regular_hours/overtime_hours for the
     * clock-out-sourced attendance rows that were affected — manual entries
     * and correction-request approvals already hardcode an 8h threshold
     * independently of this setting, so they're left untouched.
     */
    public function up(): void
    {
        if (Setting::where('key', 'attendance.overtime_threshold')->where('value', '40')->exists()) {
            Setting::where('key', 'attendance.overtime_threshold')->where('value', '40')
                ->update(['value' => '8']);

            Attendance::whereNotIn('source', ['manual'])
                ->whereNotNull('clock_out_at')
                ->where('total_hours', '>', 8)
                ->where('overtime_hours', 0)
                ->get()
                ->each(function (Attendance $attendance) {
                    $attendance->update([
                        'regular_hours'  => min((float) $attendance->total_hours, 8),
                        'overtime_hours' => max(0, (float) $attendance->total_hours - 8),
                    ]);
                });
        }
    }

    public function down(): void
    {
        // Not reversible — the pre-fix overtime_hours values are not preserved.
    }
};
