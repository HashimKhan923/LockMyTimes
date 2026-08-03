<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority,
            'requires_acknowledgment' => (bool) $this->requires_acknowledgment,
            'banner_image_url' => $this->banner_image ? asset('storage/'.$this->banner_image) : null,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'published_at' => $this->publish_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'views_count' => $this->views_count,
            // Read-state fields — populated by the controller per current
            // user (see Employee/AnnouncementController::index).
            'is_read' => $this->when(isset($this->_is_read), fn () => $this->_is_read),
            'is_acknowledged' => $this->when(isset($this->_is_acknowledged), fn () => $this->_is_acknowledged),
            'acknowledged_at' => $this->when(isset($this->_acknowledged_at), fn () => $this->_acknowledged_at),
            'needs_action' => $this->when(isset($this->_needs_action), fn () => $this->_needs_action),
        ];
    }
}
