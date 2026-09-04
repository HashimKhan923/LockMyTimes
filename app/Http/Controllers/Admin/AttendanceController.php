<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Department;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Location;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /* ================================================================
     | DASHBOARD — Today's overview
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $date = $request->get('date')
            ? Carbon::parse($request->get('date'))
            : Carbon::today();

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $query = Attendance::with(['employee.department', 'employee.position', 'location'])
            ->where('work_date', $date->toDateString());

        if ($dept = $request->get('department')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $dept));
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('remote')) {
            $query->where('is_remote_clockin', true);
        }

        if ($search = $request->get('search')) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('clock_in_at', 'desc')->paginate(30)->withQueryString();

        // Summary stats for the day
        $totalActive  = Employee::active()->count();
        $presentCount = Attendance::where('work_date', $date->toDateString())->where('status','present')->count();
        $absentCount  = $totalActive - $presentCount;
        $lateCount    = Attendance::where('work_date', $date->toDateString())->where('is_late', true)->count();
        $onLeaveCount = Attendance::where('work_date', $date->toDateString())->where('status','on_leave')->count();

        // Monthly stats for chart
        $monthlyStats = collect(range(0, 29))->map(function ($daysAgo) {
            $d = Carbon::today()->subDays($daysAgo);
            return [
                'date'    => $d->toDateString(),
                'label'   => $d->format('d'),
                'present' => Attendance::where('work_date', $d->toDateString())->where('status','present')->count(),
                'late'    => Attendance::where('work_date', $d->toDateString())->where('is_late', true)->count(),
            ];
        })->reverse()->values();

        return view('admin.attendance.index', compact(
            'records', 'date', 'departments',
            'totalActive', 'presentCount', 'absentCount', 'lateCount', 'onLeaveCount',
            'monthlyStats', 'tenant'
        ));
    }

    /* ================================================================
     | MANUAL ENTRY
     |================================================================*/
    public function manualEntry(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'work_date'    => 'required|date',
            'clock_in_at'  => 'required|date_format:H:i',
            'clock_out_at' => 'nullable|date_format:H:i|after:clock_in_at',
            'notes'        => 'nullable|string|max:255',
            'status'       => 'required|in:present,absent,half_day,on_leave,holiday',
        ]);

        $date    = Carbon::parse($data['work_date']);
        $clockIn = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d').' '.$data['clock_in_at']);
        $clockOut = isset($data['clock_out_at'])
            ? Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d').' '.$data['clock_out_at'])
            : null;

        $totalHours = $clockOut ? round($clockIn->diffInMinutes($clockOut) / 60, 2) : 0;

        Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'work_date' => $date->toDateString()],
            [
                'clock_in_at'      => $clockIn,
                'clock_out_at'     => $clockOut,
                'total_hours'      => $totalHours,
                'regular_hours'    => min($totalHours, 8),
                'overtime_hours'   => max(0, $totalHours - 8),
                'status'           => $data['status'],
                'source'           => 'manual',
                'is_manual_entry'  => true,
                'is_approved'      => true,
                'approved_by'      => auth()->id(),
                'notes'            => $data['notes'] ?? null,
            ]
        );

        return back()->with('success', 'Attendance record saved.');
    }

    /* ================================================================
     | EMPLOYEE TIMESHEET
     |================================================================*/
    public function employeeSheet(string $tenant, Request $request, Employee $employee)
    {
        $month = $request->get('month', now()->format('Y-m'));

        // A custom From/To range (e.g. "last 10 days", or a range spanning two
        // months) overrides the month picker — used for the table view below.
        // Otherwise fall back to the selected calendar month for the grid view.
        $isCustomRange = $request->filled('from') && $request->filled('to');

        if ($isCustomRange) {
            $start = Carbon::parse($request->get('from'))->startOfDay();
            $end   = Carbon::parse($request->get('to'))->startOfDay();
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }
        } else {
            $start = Carbon::parse($month.'-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        }

        $records = Attendance::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn($r) => $r->work_date->format('Y-m-d'));

        $summary = [
            'total_days'     => $records->count(),
            'present'        => $records->where('status','present')->count(),
            'late'           => $records->where('is_late', true)->count(),
            'total_hours'    => round($records->sum('total_hours'), 2),
            'overtime_hours' => round($records->sum('overtime_hours'), 2),
        ];

        return view('admin.attendance.employee-sheet',
            compact('employee', 'records', 'summary', 'start', 'end', 'month', 'tenant', 'isCustomRange'));
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $from   = $request->get('from', now()->startOfMonth()->toDateString());
        $to     = $request->get('to', now()->toDateString());
        $format = $request->get('format', 'excel');

        $records = Attendance::with(['employee.department'])
            ->whereBetween('work_date', [$from, $to])
            ->orderBy('work_date')->orderBy('employee_id')
            ->get();

        $columns = ['Emp Code', 'Name', 'Department', 'Date', 'Clock In', 'Clock Out', 'Hours', 'Status', 'Late'];

        $rows = $records->map(fn($r) => [
            $r->employee->employee_code ?? '-',
            $r->employee->full_name ?? '-',
            $r->employee->department?->name ?? '-',
            $r->work_date->format('Y-m-d'),
            $r->clock_in_at?->format('H:i') ?? '-',
            $r->clock_out_at?->format('H:i') ?? '-',
            $r->total_hours ?? '0',
            ucfirst($r->status),
            $r->is_late ? 'Yes' : 'No',
        ]);

        $filename = "attendance-{$from}-{$to}";

        if ($format === 'pdf') {
            return $exporter->pdf("Attendance Report ({$from} to {$to})", $columns, $rows, $filename.'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, $filename.'.xlsx');
    }
}