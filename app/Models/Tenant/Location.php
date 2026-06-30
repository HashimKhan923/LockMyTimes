<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name', 'code',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'timezone',
        'latitude', 'longitude', 'geofence_radius_meters',
        'phone', 'is_active', 'is_headquarters',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'decimal:7',
            'longitude'       => 'decimal:7',
            'is_active'       => 'boolean',
            'is_headquarters' => 'boolean',
        ];
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Distance (in meters) from this location to given coordinates (Haversine).
     */
    public function distanceFrom(float $lat, float $lng): float
    {
        if (! $this->latitude || ! $this->longitude) {
            return PHP_FLOAT_MAX;
        }
        $earthRadius = 6371000;
        $lat1 = deg2rad((float) $this->latitude);
        $lat2 = deg2rad($lat);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad($lng - (float) $this->longitude);
        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}