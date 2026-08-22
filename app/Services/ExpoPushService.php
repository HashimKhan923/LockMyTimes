<?php

namespace App\Services;

use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends OS-level push notifications through Expo's push service
 * (https://exp.host/--/api/v2/push/send), which relays to FCM/APNs on
 * Expo's side — no Firebase/APNs credentials needed on our end since the
 * mobile app is an EAS-managed Expo project.
 */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public static function send(User $user, string $title, string $body, array $data = []): void
    {
        if (empty($user->device_token) || ! self::isValidToken($user->device_token)) {
            return;
        }

        if (! Setting::get('notifications.push_enabled', true)) {
            return;
        }

        try {
            $response = Http::timeout(5)->post(self::ENDPOINT, [
                'to'    => $user->device_token,
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
                'sound' => 'default',
            ]);

            $error = $response->json('data.details.error');
            if ($error === 'DeviceNotRegistered') {
                $user->update(['device_token' => null]);
            }
        } catch (\Throwable $e) {
            Log::warning('Expo push send failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function isValidToken(string $token): bool
    {
        return (bool) preg_match('/^Expo(nent)?PushToken\[.+\]$/', $token);
    }
}
