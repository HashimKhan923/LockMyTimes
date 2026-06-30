<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(string $tenant)
    {
        $user = auth()->user();
        return view('admin.profile.show', compact('user', 'tenant'));
    }

    public function update(string $tenant, Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'phone'  => 'nullable|string|max:30',
            'locale' => 'nullable|string|max:10',
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(string $tenant, Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        auth()->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }
}
