<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Tenant\DeviceChangeRequest;
use App\Models\Tenant\User;
use App\Services\NotificationService;
use App\Services\MailService;
use App\Services\PasswordResetService;
use App\Services\TenantManager;
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
            'device_id' => ['required', 'string', 'max:255'],
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

        // Device lock — an employee may only sign in from the device they first
        // logged in with. A brand-new account (device_id still null) binds to
        // whichever device logs in first. A mismatch is a distinct, structured
        // error (not a plain validation failure) so the app can show a
        // "Request Device Change" action instead of "wrong password".
        if ($user->device_id && $user->device_id !== $data['device_id']) {
            return response()->json([
                'error'   => 'device_mismatch',
                'message' => 'This account is locked to another device. Ask your admin to approve a device change, or request one below.',
            ], 403);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'device_id'     => $user->device_id ?? $data['device_id'],
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
     * Request a password reset. Runs after `tenant.api` has connected the
     * tenant DB (same pre-auth route group as login — no Sanctum token
     * required/available at this point). Always returns the same generic
     * message regardless of whether the email matches an account, so the
     * response can't be used to enumerate valid emails.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $token = PasswordResetService::createToken($data['email']);
        if ($token) {
            $user = User::where('email', $data['email'])->first();
            $tenant = app(TenantManager::class)->current();
            $resetUrl = $tenant
                ? url('/t/'.$tenant->slug.'/portal/password/reset/'.$token).'?email='.urlencode($data['email'])
                : url('/');
            app(MailService::class)->sendPasswordReset($user, $resetUrl, $token);
        }

        return response()->json([
            'message' => 'If an account with that email exists, a password reset code has been sent.',
        ]);
    }

    /**
     * Complete a password reset using the token emailed by forgotPassword().
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'token'    => ['required', 'digits:6'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        if (! PasswordResetService::reset($data['email'], $data['token'], $data['password'])) {
            throw ValidationException::withMessages([
                'token' => ['This password reset code is invalid or has expired. Please request a new one.'],
            ]);
        }

        return response()->json(['message' => 'Your password has been reset. You can now log in.']);
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

    /**
     * An employee who got a new device (and is therefore locked out by the
     * device check in login()) proves their identity with their normal
     * credentials and asks an admin to clear the lock — just a reason, no
     * device details needed. No session is issued here — the employee still
     * has to log in again, normally, once approved; that later login call
     * already carries its own device_id and binds it automatically, exactly
     * like a first-ever login (see login() above).
     */
    public function requestDeviceChange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->hasRole('Employee')) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if (! $user->device_id) {
            return response()->json([
                'message' => 'This device is already authorized — no change request needed.',
            ], 422);
        }

        $existing = DeviceChangeRequest::where('user_id', $user->id)->where('status', 'pending')->first();
        $requestRow = $existing ?? new DeviceChangeRequest(['user_id' => $user->id]);
        $requestRow->fill([
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ])->save();

        $tenant = app(TenantManager::class)->current();
        NotificationService::notifyAdmins(
            "{$user->name} requested a device change (their account is locked to a previous device).",
            'device_change.requested', 'smartphone', '#F59E0B',
            $tenant ? route('admin.device-change-requests.index', $tenant->slug) : null,
            [], null
        );

        return response()->json([
            'message' => 'Your request has been sent to your admin. You will be able to log in from this device once approved.',
        ]);
    }
}
