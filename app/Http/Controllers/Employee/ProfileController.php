<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\EmergencyContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /* ================================================================
     | INDEX — Profile dashboard (single page, tabbed)
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $emp->load(['position', 'department', 'location', 'manager', 'user']);

        $emergencyContacts = EmergencyContact::where('employee_id', $emp->id)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        $tab = $request->get('tab', 'personal');

        return view('employee.profile.index', [
            'tenantSlug'        => $tenant,
            'emp'               => $emp,
            'user'              => auth()->user(),
            'emergencyContacts' => $emergencyContacts,
            'tab'               => $tab,
        ]);
    }

    /* ================================================================
     | UPDATE — Personal info (name preferences, contacts, address)
     |================================================================*/
    public function update(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        // Detect which tab posted by looking at which fields are actually present
        $tab = $request->has('address_line1') ? 'address' : 'personal';
        $successUrl = route('employee.profile.index', $tenant) . '#' . $tab;

        $validator = Validator::make($request->all(), [
            // Personal
            'preferred_name'  => ['nullable', 'string', 'max:100'],
            'personal_email'  => ['nullable', 'email', 'max:200'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'mobile'          => ['nullable', 'string', 'max:50'],
            'marital_status'  => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'separated', 'domestic_partnership'])],
            'nationality'     => ['nullable', 'string', 'max:100'],
            'date_of_birth'   => ['nullable', 'date', 'before:today'],

            // Address
            'address_line1'   => ['nullable', 'string', 'max:200'],
            'address_line2'   => ['nullable', 'string', 'max:200'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'postal_code'     => ['nullable', 'string', 'max:20'],
            'country'         => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return redirect($successUrl)->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Lock date_of_birth: only allow setting if not currently set
        if (! empty($data['date_of_birth']) && $emp->date_of_birth) {
            unset($data['date_of_birth']);
        }

        // Clean nulls into actual nulls
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        $emp->update($data);

        return redirect($successUrl)->with('success', 'Your profile has been updated.');
    }

    /* ================================================================
     | AVATAR — Upload
     |================================================================*/
    public function uploadAvatar(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Delete old avatar first
        if ($emp->avatar) {
            Storage::disk('public')->delete($emp->avatar);
        }

        $path = $request->file('avatar')->store('employees/avatars', 'public');

        $emp->update(['avatar' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }

    /* ================================================================
     | AVATAR — Remove
     |================================================================*/
    public function removeAvatar(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        if ($emp->avatar) {
            Storage::disk('public')->delete($emp->avatar);
            $emp->update(['avatar' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }

    /* ================================================================
     | PASSWORD — Change
     |================================================================*/
    public function updatePassword(string $tenant, Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $failUrl = route('employee.profile.index', $tenant) . '#security';

        if ($validator->fails()) {
            return redirect($failUrl)
                ->withErrors($validator)
                ->withInput($request->except(['current_password', 'password', 'password_confirmation']));
        }

        $data = $validator->validated();

        if (! Hash::check($data['current_password'], $user->password)) {
            return redirect($failUrl)
                ->withErrors(['current_password' => 'The current password you entered is incorrect.']);
        }

        // Reject if same as current
        if (Hash::check($data['password'], $user->password)) {
            return redirect($failUrl)
                ->withErrors(['password' => 'Your new password must be different from your current one.']);
        }

        $user->update([
            'password'             => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return redirect(route('employee.profile.index', $tenant) . '#security')
            ->with('success', 'Your password has been changed.');
    }

    /* ================================================================
     | EMERGENCY CONTACTS — Store
     |================================================================*/
    public function storeEmergencyContact(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $failUrl = route('employee.profile.index', $tenant) . '#emergency';

        $validator = Validator::make($request->all(), [
            'name'         => ['required', 'string', 'max:200'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone'        => ['required', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:200'],
            'address'      => ['nullable', 'string', 'max:500'],
            'is_primary'   => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect($failUrl)->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Enforce max of 5 emergency contacts
        $existingCount = EmergencyContact::where('employee_id', $emp->id)->count();
        if ($existingCount >= 5) {
            return redirect($failUrl)
                ->withErrors(['name' => 'You can have at most 5 emergency contacts. Please remove one before adding another.']);
        }

        $isPrimary = (bool) ($data['is_primary'] ?? false);

        // If marked primary, unmark all others
        if ($isPrimary) {
            EmergencyContact::where('employee_id', $emp->id)->update(['is_primary' => false]);
        }

        // If this is the first contact, make it primary by default
        if ($existingCount === 0) {
            $isPrimary = true;
        }

        EmergencyContact::create([
            'employee_id'  => $emp->id,
            'name'         => $data['name'],
            'relationship' => $data['relationship'],
            'phone'        => $data['phone'],
            'email'        => $data['email'] ?? null,
            'address'      => $data['address'] ?? null,
            'is_primary'   => $isPrimary,
        ]);

        return redirect($failUrl)->with('success', 'Emergency contact added.');
    }

    /* ================================================================
     | EMERGENCY CONTACTS — Update
     |================================================================*/
    public function updateEmergencyContact(string $tenant, Request $request, int $contact)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $ec = EmergencyContact::where('employee_id', $emp->id)->findOrFail($contact);

        $failUrl = route('employee.profile.index', $tenant) . '#emergency';

        $validator = Validator::make($request->all(), [
            'name'         => ['required', 'string', 'max:200'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone'        => ['required', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:200'],
            'address'      => ['nullable', 'string', 'max:500'],
            'is_primary'   => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect($failUrl)->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $isPrimary = (bool) ($data['is_primary'] ?? false);

        if ($isPrimary && ! $ec->is_primary) {
            EmergencyContact::where('employee_id', $emp->id)
                ->where('id', '!=', $ec->id)
                ->update(['is_primary' => false]);
        }

        $ec->update([
            'name'         => $data['name'],
            'relationship' => $data['relationship'],
            'phone'        => $data['phone'],
            'email'        => $data['email'] ?? null,
            'address'      => $data['address'] ?? null,
            'is_primary'   => $isPrimary,
        ]);

        return redirect($failUrl)->with('success', 'Emergency contact updated.');
    }

    /* ================================================================
     | EMERGENCY CONTACTS — Destroy
     |================================================================*/
    public function destroyEmergencyContact(string $tenant, int $contact)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $ec = EmergencyContact::where('employee_id', $emp->id)->findOrFail($contact);

        $wasPrimary = $ec->is_primary;
        $ec->delete();

        // If we deleted the primary, promote the oldest remaining one
        if ($wasPrimary) {
            $next = EmergencyContact::where('employee_id', $emp->id)
                ->orderBy('id')
                ->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return redirect(route('employee.profile.index', $tenant) . '#emergency')
            ->with('success', 'Emergency contact removed.');
    }
}