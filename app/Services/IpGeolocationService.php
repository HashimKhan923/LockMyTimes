<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Best-effort IP -> city/country lookup for remote clock-ins. This is informational only (shown
 * to admins for context, never used to block or restrict a clock-in) — a failed or slow lookup
 * must never hold up the request, so every failure mode here degrades to returning nulls.
 *
 * Uses ip-api.com's free, keyless endpoint (45 req/min limit, no SLA) as a reasonable default for
 * a first release. If the client needs higher volume or an SLA, swap the request in lookup() for
 * a paid provider (e.g. ipinfo.io, MaxMind) — nothing else in the app needs to change, callers
 * only see the normalized ['city' => ?, 'country' => ?] shape below.
 */
class IpGeolocationService
{
    public function lookup(?string $ip): array
    {
        $empty = ['city' => null, 'country' => null];

        if (! $ip || $this->isPrivateOrLoopback($ip)) {
            return $empty;
        }

        return Cache::remember("ip-geo:{$ip}", now()->addDay(), function () use ($ip, $empty) {
            try {
                $response = Http::timeout(2)
                    ->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,city,countryCode']);
            } catch (\Throwable $e) {
                Log::warning("IP geolocation lookup failed for {$ip}: ".$e->getMessage());

                return $empty;
            }

            if ($response->failed() || $response->json('status') !== 'success') {
                return $empty;
            }

            return [
                'city' => $response->json('city'),
                'country' => $response->json('countryCode'),
            ];
        });
    }

    private function isPrivateOrLoopback(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
