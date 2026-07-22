<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'column_type' => $this->column_type,
            'sort_order' => $this->sort_order,
            'wip_limit' => $this->wip_limit,
            'is_closed_status' => (bool) $this->is_closed_status,
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
