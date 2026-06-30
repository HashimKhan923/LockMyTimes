<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Main\Tenant;
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
            return redirect()->route('admin.dashboard', $tenant);
        }

        return view('admin.auth.login', compact('tenantModel'));
    }

    public function login(Request $request, string $tenant)
    {
        $tenantModel = Tenant::where('slug', $tenant)->firstOrFail();

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Switch to tenant DB before attempting auth
        app(TenantManager::class)->connect($tenantModel);

        if (! Auth::guard('web')->attempt(
            ['email' => $request->email, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'These credentials do not match our records.');
        }

        $user = Auth::guard('web')->user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            return back()->with('error', 'Your account has been deactivated. Contact your HR admin.');
        }

        // Block pure Employee-role users — they belong on the employee portal.
        // Custom roles (not Employee) are allowed into the admin panel.
        if ($user->hasRole('Employee') && ! $user->hasAnyRole(['Tenant Admin', 'HR Manager', 'Manager'])) {
            Auth::guard('web')->logout();
            return back()->with('error', 'You do not have access to the admin portal. Please use the employee portal.');
        }

        if ($user->roles()->count() === 0) {
            Auth::guard('web')->logout();
            return back()->with('error', 'Your account has no role assigned. Contact your administrator.');
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        // Must change password flow
        if ($user->must_change_password) {
            return redirect()->route('admin.password.change', $tenant);
        }

        return redirect()->route('admin.dashboard', $tenant);
    }

    public function showChangePassword(string $tenant)
    {
        return view('admin.auth.change-password', ['tenantSlug' => $tenant]);
    }

    public function changePassword(Request $request, string $tenant)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = Auth::guard('web')->user();
        $user->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()
            ->route('admin.dashboard', $tenant)
            ->with('success', 'Password changed successfully. Welcome to Lockmytimes!');
    }

    public function logout(Request $request, string $tenant)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login', $tenant);
    }
}