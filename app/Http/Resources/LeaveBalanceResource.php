<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects the pre-computed object shape built in the controller (id, type_id,
 * name, color, is_paid, total, used, pending, available, used_pct) — mirrors
 * Employee/LeaveController::index's balance mapping exactly.
 */
class LeaveBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_id' => $this->type_id,
            'name' => $this->name,
            'color' => $this->color,
            'is_paid' => $this->is_paid,
            'total' => $this->total,
            'used' => $this->used,
            'pending' => $this->pending,
            'available' => $this->available,
            'used_pct' => $this->used_pct,
        ];
    }
}
