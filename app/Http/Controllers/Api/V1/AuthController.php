<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mobile login. Runs after `tenant.api` has already connected the
     * tenant DB. Validates credentials directly against the tenant `User`
     * model (no session — issues a Sanctum token instead).
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if ($reason = $user->loginBlockReason()) {
            throw ValidationException::withMessages([
                'email' => [$reason],
            ]);
        }

        if (! $user->hasRole('Employee')) {
            throw ValidationException::withMessages([
                'email' => ['This app is for employees only.'],
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        if (! empty($data['device_token'])) {
            $user->update(['device_token' => $data['device_token']]);
        }

        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    /**
     * Change (or force-change) the current user's password.
     * `current_password` is only required when the account is not under
     * a forced must-change-password state.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];

        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'string'];
        }

        $data = $request->validate($rules);

        if (! $user->must_change_password) {
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password you entered is incorrect.'],
                ]);
            }

            if (Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => ['Your new password must be different from your current one.'],
                ]);
            }
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Password updated.', 'user' => new UserResource($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        // Prevent push notifications continuing to arrive for this device
        // under the account that just signed out (device may be shared, or
        // a different employee may sign in next). The app re-registers the
        // token on its next authenticated session.
        $user->update(['device_token' => null]);

        return response()->json(['message' => 'Signed out.']);
    }

    /**
     * Revoke every Sanctum token for this user except the one used for
     * the current request (mobile analog of the web "sign out other
     * sessions" security setting).
     */
    public function signOutOtherSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()->id;

        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json(['message' => 'All other sessions have been signed out.']);
    }
}
