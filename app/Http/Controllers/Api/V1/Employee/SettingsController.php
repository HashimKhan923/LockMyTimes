<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Tenant\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * JSON mirror of Employee/SettingsController. Sign-out-other-sessions
 * already lives on Api\V1\AuthController (Sanctum token revocation, built in
 * Milestone 0) rather than being duplicated here.
 */
class SettingsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'locale' => ['required', Rule::in(['en', 'es', 'fr', 'ar'])],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', Rule::in(['DMY', 'MDY', 'YMD'])],
            'time_format' => ['required', Rule::in(['12', '24'])],
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Preferences saved.', 'user' => new UserResource($user)]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $keys = array_keys(\App\Models\Tenant\User::defaultNotificationPreferences());

        $data = $request->validate([
            'preferences' => ['required', 'array'],
        ]);

        $prefs = [];
        foreach ($keys as $key) {
            $prefs[$key] = [
                'in_app' => (bool) ($data['preferences'][$key]['in_app'] ?? false),
                'email' => (bool) ($data['preferences'][$key]['email'] ?? false),
            ];
        }

        $user->update(['notification_preferences' => $prefs]);

        return response()->json(['message' => 'Notification preferences saved.', 'user' => new UserResource($user)]);
    }

    public function updatePrivacy(Request $request): JsonResponse
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403);

        $keys = array_keys(Employee::defaultPrivacySettings());
        $data = $request->validate(['settings' => ['required', 'array']]);

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = (bool) ($data['settings'][$key] ?? false);
        }

        $emp->update(['privacy_settings' => $settings]);

        return response()->json(['message' => 'Privacy settings saved.', 'privacy_settings' => $settings]);
    }
}
