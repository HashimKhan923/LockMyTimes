<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DeviceChangeRequest;
use Illuminate\Http\Request;

class DeviceChangeRequestController extends Controller
{
    public function index(string $tenant)
    {
        $requests = DeviceChangeRequest::with(['user.employee', 'reviewer'])
            ->orderByRaw("status = 'pending' desc")
            ->latest('id')
            ->paginate(20);

        return view('admin.device-change-requests.index', [
            'tenantSlug' => $tenant,
            'requests'   => $requests,
        ]);
    }

    public function approve(string $tenant, DeviceChangeRequest $deviceChangeRequest)
    {
        if ($deviceChangeRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        // Clear the lock — the employee's next successful login (from the device
        // they already tried and that raised this request) binds it as their new one.
        $deviceChangeRequest->user->update(['device_id' => null]);

        $deviceChangeRequest->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Device lock cleared for {$deviceChangeRequest->user->name}. They can now log in from their new device.");
    }

    public function reject(string $tenant, Request $request, DeviceChangeRequest $deviceChangeRequest)
    {
        if ($deviceChangeRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $data = $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $deviceChangeRequest->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'reason'      => $data['rejection_reason'] ?? $deviceChangeRequest->reason,
        ]);

        return back()->with('success', 'Device change request rejected.');
    }
}
