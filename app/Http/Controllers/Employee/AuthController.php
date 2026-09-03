<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Main\Tenant;
use App\Models\Tenant\User;
use App\Services\MailService;
use App\Services\PasswordResetService;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(string $tenant)
    {
        $tenantModel = Tenant::where('slug', $tenant)->firstOrFail();

        if (Auth::guard('web')->check()) {
            return redirect()->route('employee.dashboard', $tenant);
        }

        return view('employee.auth.login', compact('tenantModel'));
    }

    public function login(Request $request, string $tenant)
    {
        $tenantModel = Tenant::where('slug', $tenant)->firstOrFail();

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        app(TenantManager::class)->connect($tenantModel);

        if (! Auth::guard('web')->attempt(
            ['email' => $request->email, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Invalid email or password.');
        }

        $user = Auth::guard('web')->user();

        if ($reason = $user->loginBlockReason()) {
            Auth::guard('web')->logout();
            return back()->with('error', $reason);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('employee.password.change', $tenant);
        }

        return redirect()->route('employee.dashboard', $tenant);
    }

    public function showForgotPassword(string $tenant)
    {
        $tenantModel = Tenant::where('slug', $tenant)->firstOrFail();
        return view('employee.auth.forgot-password', compact('tenantModel'));
    }

    public function forgotPassword(Request $request, string $tenant)
    {
        $tenantModel = Tenant::where('slug', $tenant)->firstOrFail();
        $request->validate(['email' => 'required|email']);

        app(TenantManager::class)->connect($tenantModel);

        $token = PasswordResetService::createToken($request->email);
        if ($token) {
            $user = User::where('email', $request->email)->first();
            $resetUrl = route('employee.password.reset', [$tenant, $token]).'?email='.urlencode($request->email);
            app(MailService::class)->sendPasswordReset($user, $resetUrl, $token);
        }

        // Same message whether or not the email exists — don't reveal which accounts exist.
        return back()->with('success', 'If an account with that email exists, a password reset link has been sent.');
    }

    public function showResetPassword(string $tenant, string $token, Request $request)
    {
        $tenantModel = Tenant::where('slug', $tenant)->firstOrFail();
        $email = $request->query('email', '');

        return view('employee.auth.reset-password', compact('tenantModel', 'token', 'email'));
    }

    public function resetPassword(Request $request, string $tenant)
    {
        $tenantModel = Tenant::where('slug', $tenant)->firstOrFail();

        $request->validate([
            'token'    => 'required|digits:6',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        app(TenantManager::class)->connect($tenantModel);

        if (! PasswordResetService::reset($request->email, $request->token, $request->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'This password reset link is invalid or has expired. Please request a new one.');
        }

        return redirect()
            ->route('employee.login', $tenant)
            ->with('success', 'Your password has been reset. You can now log in.');
    }

    public function showChangePassword(string $tenant)
    {
        return view('employee.auth.change-password', ['tenantSlug' => $tenant]);
    }

    public function changePassword(Request $request, string $tenant)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        Auth::guard('web')->user()->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()
            ->route('employee.dashboard', $tenant)
            ->with('success', 'Password updated. Welcome!');
    }

    public function logout(Request $request, string $tenant)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('employee.login', $tenant);
    }
}