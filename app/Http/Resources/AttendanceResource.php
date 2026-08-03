<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_date' => $this->work_date?->toDateString(),
            'status' => $this->status,
            'clock_in_at' => $this->clock_in_at?->toIso8601String(),
            'clock_out_at' => $this->clock_out_at?->toIso8601String(),
            'total_hours' => (float) $this->total_hours,
            'regular_hours' => (float) $this->regular_hours,
            'overtime_hours' => (float) $this->overtime_hours,
            'break_hours' => (float) $this->break_hours,
            'is_late' => (bool) $this->is_late,
            'late_minutes' => (int) $this->late_minutes,
            'is_early_out' => (bool) $this->is_early_out,
            'early_minutes' => (int) $this->early_minutes,
            'is_geofence_breach' => (bool) $this->is_geofence_breach,
            'source' => $this->source,
            'notes' => $this->notes,
            'location' => new LocationResource($this->whenLoaded('location')),
            'breaks' => AttendanceBreakResource::collection($this->whenLoaded('breaks')),
        ];
    }
}
