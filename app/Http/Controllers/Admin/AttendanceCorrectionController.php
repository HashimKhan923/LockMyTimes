<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AttendanceCorrectionRequest;
use App\Models\Tenant\Employee;
use App\Models\Tenant\User;
use App\Services\AttendanceCorrectionService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    public function index(string $tenant, Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = AttendanceCorrectionRequest::with(['employee.department', 'approver'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($empId = $request->get('employee')) {
            $query->where('employee_id', $empId);
        }

        $requests = $query->paginate(20)->withQueryString();

        $stats = [
            'pending'  => AttendanceCorrectionRequest::where('status', 'pending')->count(),
            'approved' => AttendanceCorrectionRequest::where('status', 'approved')
                              ->whereMonth('approved_at', now()->month)->count(),
            'rejected' => AttendanceCorrectionRequest::where('status', 'rejected')
                              ->whereMonth('approved_at', now()->month)->count(),
        ];

        $employees = Employee::active()->orderBy('first_name')->get();

        return view('admin.attendance-corrections.index', compact('requests', 'stats', 'employees', 'status', 'tenant'));
    }

    public function approve(string $tenant, AttendanceCorrectionRequest $correction)
    {
        if ($correction->status !== 'pending') {
            return back()->with('error', 'This correction request has already been processed.');
        }

        $employee = $correction->employee;

        AttendanceCorrectionService::approve($correction, auth()->id());

        $employeeUser = User::where('employee_id', $correction->employee_id)->first();
        if ($employeeUser) {
            NotificationService::attendanceCorrectionApproved(
                $employeeUser, auth()->user()->name,
                route('employee.attendance-corrections.index', $tenant)
            );
        }

        return back()->with('success', "{$employee->full_name}'s attendance correction approved.");
    }

    public function reject(string $tenant, Request $request, AttendanceCorrectionRequest $correction)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($correction->status !== 'pending') {
            return back()->with('error', 'This correction request has already been processed.');
        }

        $correction->update([
            'status'            => 'rejected',
            'approved_by'       => auth()->id(),
            'approved_at'       => now(),
            'rejection_reason'  => $request->reason,
        ]);

        $employeeUser = User::where('employee_id', $correction->employee_id)->first();
        if ($employeeUser) {
            NotificationService::attendanceCorrectionRejected(
                $employeeUser, auth()->user()->name,
                route('employee.attendance-corrections.index', $tenant)
            );
        }

        return back()->with('success', 'Attendance correction request rejected.');
    }
}
