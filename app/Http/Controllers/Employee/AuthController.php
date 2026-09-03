<?php

namespace App\Http\Controllers\Employee;

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