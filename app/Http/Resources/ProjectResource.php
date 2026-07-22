<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color ?? '#6C7DF7',
            'status' => $this->status,
            'priority' => $this->priority,
            'progress' => $this->progress,
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'is_overdue' => $this->is_overdue,
            'manager_name' => $this->whenLoaded('manager', fn () => $this->manager?->full_name),
            // Per-current-employee counters — populated by the controller
            // (see ProjectController::index) for the same-shaped web page.
            'my_total' => $this->when(isset($this->_my_total), fn () => $this->_my_total),
            'my_open' => $this->when(isset($this->_my_open), fn () => $this->_my_open),
            'my_overdue' => $this->when(isset($this->_my_overdue), fn () => $this->_my_overdue),
            'my_role' => $this->when(isset($this->_my_role), fn () => $this->_my_role),
        ];
    }
}
