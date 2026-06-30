<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Department;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Shift;
use App\Models\Tenant\ShiftAssignment;
use App\Models\Tenant\ShiftSwapRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /* ================================================================
     | INDEX — shift list + this week's schedule
     |================================================================*/
    public function index(string $tenant)
    {
        $shifts      = Shift::withCount('assignments')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        // This week's assignments for the schedule grid
        $weekStart   = Carbon::now()->startOfWeek();
        $weekDays    = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i));

        $weekAssignments = ShiftAssignment::with(['employee.department', 'shift'])
            ->where('start_date', '<=', $weekStart->copy()->endOfWeek()->toDateString())
            ->where(fn($q) => $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $weekStart->toDateString()))
            ->get()
            ->groupBy('employee_id');

        // Pending swap requests
        $pendingSwaps = ShiftSwapRequest::with([
            'requester', 'targetEmployee', 'requesterShift', 'targetShift',
        ])->where('status', 'pending')->latest()->get();

        $stats = [
            'total_shifts'      => $shifts->count(),
            'assigned_employees'=> ShiftAssignment::active()->distinct('employee_id')->count(),
            'unassigned'        => Employee::active()->count()
                                 - ShiftAssignment::active()->distinct('employee_id')->count(),
            'pending_swaps'     => $pendingSwaps->count(),
        ];

        return view('admin.shifts.index', compact(
            'shifts', 'departments', 'weekDays', 'weekAssignments',
            'pendingSwaps', 'stats', 'tenant'
        ));
    }

    /* ================================================================
     | STORE — create new shift template
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'code'                 => 'nullable|string|max:20',
            'start_time'           => 'required|date_format:H:i',
            'end_time'             => 'required|date_format:H:i',
            'break_duration_minutes' => 'nullable|integer|min:0|max:120',
            'working_days'         => 'required|array|min:1',
            'working_days.*'       => 'in:0,1,2,3,4,5,6',
            'color'                => 'nullable|string|max:7',
            'description'          => 'nullable|string',
            'is_overnight'         => 'boolean',
            'grace_period_minutes' => 'nullable|integer|min:0|max:60',
        ]);

        $data['working_days'] = $request->input('working_days', []);
        $data['is_active']    = true;

        // Calculate shift duration
        $start    = Carbon::createFromFormat('H:i', $data['start_time']);
        $end      = Carbon::createFromFormat('H:i', $data['end_time']);
        $minutes  = $data['is_overnight'] ?? false
            ? $start->diffInMinutes($end->copy()->addDay())
            : $start->diffInMinutes($end);
        $data['total_hours'] = round($minutes / 60, 2);

        Shift::create($data);

        return back()->with('success', "Shift \"{$data['name']}\" created successfully.");
    }

    /* ================================================================
     | UPDATE — edit shift template
     |================================================================*/
    public function update(string $tenant, Request $request, Shift $shift)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'start_time'             => 'required|date_format:H:i',
            'end_time'               => 'required|date_format:H:i',
            'break_duration_minutes' => 'nullable|integer|min:0|max:120',
            'working_days'           => 'required|array|min:1',
            'color'                  => 'nullable|string|max:7',
            'grace_period_minutes'   => 'nullable|integer|min:0|max:60',
            'is_active'              => 'boolean',
        ]);

        $data['working_days'] = $request->input('working_days', []);

        $start   = Carbon::createFromFormat('H:i', $data['start_time']);
        $end     = Carbon::createFromFormat('H:i', $data['end_time']);
        $minutes = $start->diffInMinutes($end);
        $data['total_hours'] = round($minutes / 60, 2);

        $shift->update($data);

        return back()->with('success', "Shift \"{$shift->name}\" updated.");
    }

    /* ================================================================
     | DESTROY — delete shift template
     |================================================================*/
    public function destroy(string $tenant, Shift $shift)
    {
        if ($shift->assignments()->count() > 0) {
            return back()->with('error', 'Cannot delete a shift with active assignments. Unassign employees first.');
        }
        $shift->delete();
        return back()->with('success', 'Shift deleted.');
    }

    /* ================================================================
     | ASSIGN — assign employees to a shift
     |================================================================*/
    public function assign(string $tenant, Request $request)
    {
        $data = $request->validate([
            'shift_id'     => 'required|exists:shifts,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
        ]);

        $assigned = 0;
        foreach ($data['employee_ids'] as $empId) {
            // End any existing active assignment for this employee
            ShiftAssignment::where('employee_id', $empId)
                ->whereNull('end_date')
                ->update(['end_date' => Carbon::parse($data['start_date'])->subDay()->toDateString()]);

            ShiftAssignment::create([
                'shift_id'    => $data['shift_id'],
                'employee_id' => $empId,
                'start_date'  => $data['start_date'],
                'end_date'    => $data['end_date'] ?? null,
                'assigned_by' => auth()->id(),
            ]);
            $assigned++;
        }

        return back()->with('success', "{$assigned} employee(s) assigned to shift.");
    }

    /* ================================================================
     | UNASSIGN — remove employee from shift
     |================================================================*/
    public function unassign(string $tenant, ShiftAssignment $assignment)
    {
        $assignment->update(['end_date' => today()->toDateString()]);
        return back()->with('success', 'Employee removed from shift.');
    }

    /* ================================================================
     | SWAP REQUESTS — approve / reject
     |================================================================*/
    public function approveSwap(string $tenant, ShiftSwapRequest $swap)
    {
        // Swap the shift assignments between the two employees
        $requesterAssignment = ShiftAssignment::where('employee_id', $swap->requester_id)
            ->where('shift_id', $swap->requester_shift_id)
            ->whereNull('end_date')->first();

        $targetAssignment = ShiftAssignment::where('employee_id', $swap->target_employee_id)
            ->where('shift_id', $swap->target_shift_id)
            ->whereNull('end_date')->first();

        if ($requesterAssignment && $targetAssignment) {
            [$requesterAssignment->shift_id, $targetAssignment->shift_id] =
                [$targetAssignment->shift_id, $requesterAssignment->shift_id];
            $requesterAssignment->save();
            $targetAssignment->save();
        }

        $swap->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Shift swap approved.');
    }

    public function rejectSwap(string $tenant, Request $request, ShiftSwapRequest $swap)
    {
        $swap->update([
            'status'          => 'rejected',
            'rejection_reason'=> $request->get('reason', ''),
            'approved_by'     => auth()->id(),
        ]);
        return back()->with('success', 'Shift swap rejected.');
    }

    /* ================================================================
     | EMPLOYEE SCHEDULE — individual view
     |================================================================*/
    public function employeeSchedule(string $tenant, Employee $employee, Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $assignments = ShiftAssignment::with('shift')
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $end->toDateString())
            ->where(fn($q) => $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $start->toDateString()))
            ->orderBy('start_date')
            ->get();

        return view('admin.shifts.employee-schedule',
            compact('employee', 'assignments', 'start', 'end', 'month', 'tenant'));
    }
}