<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskAssigneeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->whenLoaded('employee', fn () => $this->employee?->full_name),
            'avatar_url' => $this->whenLoaded('employee', fn () => $this->employee?->avatar_url),
            'is_primary' => (bool) $this->is_primary,
        ];
    }
}
