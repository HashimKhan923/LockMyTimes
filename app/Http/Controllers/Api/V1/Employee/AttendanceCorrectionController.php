<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceCorrectionResource;
use App\Models\Tenant\AttendanceCorrectionRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403);

        $requests = AttendanceCorrectionRequest::where('employee_id', $emp->id)
            ->latest('work_date')
            ->paginate(20);

        return response()->json([
            'requests' => AttendanceCorrectionResource::collection($requests->items()),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'total'        => $requests->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $emp = $request->user()->employee;
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
            $tenant = $request->header('X-Tenant');
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

        return response()->json([
            'message' => "Correction request {$correction->request_number} submitted. Awaiting approval.",
            'request' => new AttendanceCorrectionResource($correction),
        ], 201);
    }

    public function cancel(Request $request, AttendanceCorrectionRequest $correction): JsonResponse
    {
        $emp = $request->user()->employee;
        abort_unless($emp && $correction->employee_id === $emp->id, 403);

        if ($correction->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be cancelled.'], 422);
        }

        $correction->update(['status' => 'cancelled']);

        return response()->json(['success' => true]);
    }
}
