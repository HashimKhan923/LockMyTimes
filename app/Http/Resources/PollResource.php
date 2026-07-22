<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'description' => $this->description,
            'type' => $this->type,
            'options' => collect($this->options ?? [])->map(
                fn ($o) => is_array($o) ? ($o['text'] ?? $o['label'] ?? '') : (string) $o
            )->values(),
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            // Populated by the controller per current user (see
            // Employee/AnnouncementController).
            'has_voted' => $this->when(isset($this->_has_voted), fn () => $this->_has_voted),
            'my_choices' => $this->when(isset($this->_my_choices), fn () => $this->_my_choices),
            'is_active' => $this->when(isset($this->_is_active), fn () => $this->_is_active),
        ];
    }
}
