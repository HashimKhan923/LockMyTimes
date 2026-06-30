<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        $admin  = Auth::guard('superadmin')->user();
        $agents = SuperAdmin::orderBy('name')->get();

        $platformSettings = [
            'app_name'       => config('app.name', 'LockMyTime'),
            'trial_days'     => config('tenancy.trial_days', 14),
            'support_email'  => config('mail.from.address', ''),
        ];

        return view('superadmin.settings.index', compact('admin', 'agents', 'platformSettings'));
    }

    public function updateProfile(Request $request)
    {
        $admin = Auth::guard('superadmin')->user();

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:main.super_admins,email,' . $admin->id,
            'phone' => 'nullable|string|max:30',
        ]);

        $admin->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('superadmin')->user();

        $request->validate([
            'current_password' => ['required', fn($attr, $val, $fail) => Hash::check($val, $admin->password) ?: $fail('Current password is incorrect.')],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $admin->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function inviteAgent(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:main.super_admins,email',
            'role'  => 'required|in:owner,support,analyst',
        ]);

        $tempPassword = Str::random(16);

        SuperAdmin::create(array_merge($data, [
            'password'  => Hash::make($tempPassword),
            'is_active' => true,
        ]));

        return back()->with('success', "Agent {$data['name']} added. Temporary password: {$tempPassword}");
    }

    public function toggleAgent(SuperAdmin $agent)
    {
        if ($agent->id === Auth::guard('superadmin')->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $agent->update(['is_active' => !$agent->is_active]);
        $state = $agent->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Agent {$agent->name} {$state}.");
    }

    public function destroyAgent(SuperAdmin $agent)
    {
        if ($agent->id === Auth::guard('superadmin')->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $agent->name;
        $agent->delete();

        return back()->with('success', "Agent \"{$name}\" removed.");
    }
}
