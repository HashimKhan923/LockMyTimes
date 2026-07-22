<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'avatar_url' => $this->avatar_url,
            'position' => $this->whenLoaded('position', fn () => $this->position?->title),
            'department' => $this->whenLoaded('department', fn () => $this->department?->name),
            // Per-report metrics — populated by the controller (see
            // TeamController::index) for the same-shaped web page.
            'status_today' => $this->when(isset($this->_status_today), fn () => $this->_status_today),
            'pending_leaves' => $this->when(isset($this->_pending_leaves), fn () => $this->_pending_leaves),
            'pending_expenses' => $this->when(isset($this->_pending_expenses), fn () => $this->_pending_expenses),
            'open_tasks' => $this->when(isset($this->_open_tasks), fn () => $this->_open_tasks),
        ];
    }
}
