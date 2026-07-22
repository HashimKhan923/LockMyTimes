<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'user_avatar_url' => $this->whenLoaded('user', fn () => $this->user?->avatar_url),
            'is_edited' => (bool) $this->is_edited,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
