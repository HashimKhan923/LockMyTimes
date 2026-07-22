<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'total_days' => (float) $this->total_days,
            'day_part' => $this->day_part,
            'reason' => $this->reason,
            'contact_during_leave' => $this->contact_during_leave,
            'attachments' => collect($this->attachments ?? [])->map(fn ($a) => [
                'name' => $a['name'] ?? null,
                'url' => isset($a['path']) ? Storage::disk('public')->url($a['path']) : null,
                'size' => $a['size'] ?? null,
            ])->values(),
            'status' => $this->status,
            'approver_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approver_comments' => $this->approver_comments,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
