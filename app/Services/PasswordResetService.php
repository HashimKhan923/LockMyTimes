<?php

namespace App\Services;

use App\Models\Tenant\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Shared forgot/reset-password logic for the admin portal, employee portal,
 * and mobile API — all three sit on the same App\Models\Tenant\User table
 * (see config/auth.php's single 'users' guard/provider), so the token
 * plumbing lives in one place; only the reset-link URL and the calling
 * controller's response shape (redirect vs JSON) differ per surface.
 *
 * Must be called with the tenant DB connection already active (true for
 * every route this is used from — IdentifyTenant / IdentifyTenantFromToken
 * both connect it before the controller runs).
 */
class PasswordResetService
{
    private const TABLE = 'password_reset_tokens';

    /**
     * Create (or replace) a reset code for this email if a matching user
     * exists. Returns the plain 6-digit code to embed in the email, or null
     * if no such user exists — callers should still report a generic
     * "check your email" success either way, to avoid leaking which emails
     * have an account.
     */
    public static function createToken(string $email): ?string
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return null;
        }

        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::connection('tenant')->table(self::TABLE)->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        return $token;
    }

    public static function tokenIsValid(string $email, string $token): bool
    {
        $record = DB::connection('tenant')->table(self::TABLE)->where('email', $email)->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            return false;
        }

        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        return ! Carbon::parse($record->created_at)->addMinutes($expireMinutes)->isPast();
    }

    /**
     * Verify the token and set the new password. Returns false (no changes
     * made) if the token is missing/expired/mismatched or the user vanished.
     */
    public static function reset(string $email, string $token, string $newPassword): bool
    {
        if (! self::tokenIsValid($email, $token)) {
            return false;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return false;
        }

        $user->update([
            'password'             => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        DB::connection('tenant')->table(self::TABLE)->where('email', $email)->delete();

        return true;
    }
}
