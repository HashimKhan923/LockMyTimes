<?php

namespace App\Services;

use App\Models\Tenant\Location;

class GeofenceService
{
    /**
     * Check if given coordinates are within the location's geofence.
     *
     * @return array{valid: bool, distance: int, radius: int}
     */
    public function check(Location $location, float $lat, float $lng): array
    {
        if (! $location->latitude || ! $location->longitude) {
            // No geofence set — allow by default
            return ['valid' => true, 'distance' => 0, 'radius' => 0];
        }

        $distance = (int) round($this->haversine(
            (float) $location->latitude,
            (float) $location->longitude,
            $lat,
            $lng
        ));

        $radius = $location->geofence_radius_meters ?? 100;

        return [
            'valid'    => $distance <= $radius,
            'distance' => $distance,
            'radius'   => $radius,
        ];
    }

    /**
     * Haversine formula — returns distance in metres between two lat/lng pairs.
     */
    public function haversine(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $earthRadius = 6371000; // metres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}