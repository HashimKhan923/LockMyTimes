<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color ?? '#6C7DF7',
            'is_paid' => (bool) $this->is_paid,
            'default_days_per_year' => (float) $this->default_days_per_year,
            'requires_approval' => (bool) $this->requires_approval,
            'requires_documentation' => (bool) $this->requires_documentation,
            'max_consecutive_days' => $this->max_consecutive_days,
            'notice_days_required' => $this->notice_days_required,
        ];
    }
}
