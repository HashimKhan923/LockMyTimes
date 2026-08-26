<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AttendanceCorrectionRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionController extends Controller
{
    public function index(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $requests = AttendanceCorrectionRequest::where('employee_id', $emp->id)
            ->latest('work_date')
            ->paginate(15);

        return view('employee.attendance-corrections.index', compact('requests', 'tenant'));
    }

    public function create(string $tenant)
    {
        return view('employee.attendance-corrections.create', compact('tenant'));
    }

    public function store(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'clock_in'  => ['nullable', 'date_format:H:i', 'required_without:clock_out'],
            'clock_out' => ['nullable', 'date_format:H:i', 'required_without:clock_in', 'after:clock_in'],
            'reason'    => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $duplicate = AttendanceCorrectionRequest::where('employee_id', $emp->id)
            ->where('work_date', $data['work_date'])
            ->where('status', 'pending')
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'work_date' => 'You already have a pending correction request for this date.',
            ]);
        }

        $correction = AttendanceCorrectionRequest::create([
            'employee_id'        => $emp->id,
            'request_number'     => AttendanceCorrectionRequest::generateNumber(),
            'work_date'          => $data['work_date'],
            'proposed_clock_in'  => $data['clock_in'] ?? null,
            'proposed_clock_out' => $data['clock_out'] ?? null,
            'reason'             => $data['reason'],
            'status'             => 'pending',
        ]);

        try {
            $managerUser = $emp->manager?->user;
            if ($managerUser) {
                NotificationService::send(
                    $managerUser,
                    "{$emp->full_name} requested an attendance correction",
                    'attendance.correction_requested',
                    'clock',
                    '#F59E0B',
                    route('employee.team.approvals.corrections', $tenant)
                );
            }
            NotificationService::notifyAdmins(
                "{$emp->full_name} requested an attendance correction",
                'attendance.correction_requested', 'clock', '#F59E0B',
                route('admin.attendance-corrections.index', $tenant),
                [], $managerUser?->id
            );
        } catch (\Throwable $e) {
            \Log::error('Attendance correction notification failed: '.$e->getMessage());
        }

        return redirect()
            ->route('employee.attendance-corrections.index', $tenant)
            ->with('success', "Correction request {$correction->request_number} submitted. Awaiting approval.");
    }

    public function cancel(string $tenant, AttendanceCorrectionRequest $correction)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp && $correction->employee_id === $emp->id, 403);

        if ($correction->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $correction->update(['status' => 'cancelled']);

        return back()->with('success', 'Correction request cancelled.');
    }
}
