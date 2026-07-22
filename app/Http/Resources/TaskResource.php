<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'task_list_id' => $this->task_list_id,
            'parent_task_id' => $this->parent_task_id,
            'task_code' => $this->task_code,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'progress' => $this->progress,
            'due_date' => $this->due_date?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'estimated_hours' => $this->estimated_hours,
            'is_overdue' => $this->is_overdue,
            'comments_count' => $this->comments_count,
            'subtasks_count' => $this->subtasks_count,
            'completed_subtasks_count' => $this->completed_subtasks_count,
            'created_by' => $this->created_by,
            'sort_order' => $this->sort_order,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'task_list' => new TaskListResource($this->whenLoaded('taskList')),
            'assignees' => TaskAssigneeResource::collection($this->whenLoaded('assignees')),
            'checklists' => TaskChecklistResource::collection($this->whenLoaded('checklists')),
            'comments' => TaskCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => TaskAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
