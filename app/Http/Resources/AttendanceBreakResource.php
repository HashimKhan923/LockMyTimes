<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceBreakResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'break_type' => $this->break_type,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'duration_minutes' => (float) $this->duration_minutes,
            'is_paid' => (bool) $this->is_paid,
            'notes' => $this->notes,
        ];
    }
}
