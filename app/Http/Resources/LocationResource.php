<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'geofence_radius_meters' => $this->geofence_radius_meters,
            'is_headquarters' => (bool) $this->is_headquarters,
            'timezone' => $this->timezone,
            'requires_qr' => (bool) ($this->active_qr_count ?? $this->qrCodes()->where('is_active', true)->count()),
        ];
    }
}
