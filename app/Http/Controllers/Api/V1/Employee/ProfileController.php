<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmergencyContactResource;
use App\Http\Resources\EmployeeResource;
use App\Models\Tenant\EmergencyContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * JSON mirror of Employee/ProfileController — same validation rules, no
 * Blade/redirect branches. Password change lives in Api\V1\AuthController
 * (already built in Milestone 0) rather than being duplicated here.
 */
class ProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $emp->load(['position', 'department', 'location', 'manager']);

        $emergencyContacts = EmergencyContact::where('employee_id', $emp->id)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        return response()->json([
            'employee' => new EmployeeResource($emp),
            'emergency_contacts' => EmergencyContactResource::collection($emergencyContacts),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $validator = Validator::make($request->all(), [
            'preferred_name' => ['nullable', 'string', 'max:100'],
            'personal_email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'separated', 'domestic_partnership'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (! empty($data['date_of_birth']) && $emp->date_of_birth) {
            unset($data['date_of_birth']);
        }

        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        $emp->update($data);

        return response()->json([
            'message' => 'Your profile has been updated.',
            'employee' => new EmployeeResource($emp->fresh(['position', 'department', 'location', 'manager'])),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($emp->avatar) {
            Storage::disk('public')->delete($emp->avatar);
        }

        $path = $request->file('avatar')->store('employees/avatars', 'public');
        $emp->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Profile photo updated.',
            'employee' => new EmployeeResource($emp->fresh()),
        ]);
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        if ($emp->avatar) {
            Storage::disk('public')->delete($emp->avatar);
            $emp->update(['avatar' => null]);
        }

        return response()->json([
            'message' => 'Profile photo removed.',
            'employee' => new EmployeeResource($emp->fresh()),
        ]);
    }

    public function storeEmergencyContact(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:200'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $existingCount = EmergencyContact::where('employee_id', $emp->id)->count();
        if ($existingCount >= 5) {
            return response()->json(['message' => 'You can have at most 5 emergency contacts. Please remove one before adding another.'], 422);
        }

        $isPrimary = (bool) ($data['is_primary'] ?? false);

        if ($isPrimary) {
            EmergencyContact::where('employee_id', $emp->id)->update(['is_primary' => false]);
        }
        if ($existingCount === 0) {
            $isPrimary = true;
        }

        $ec = EmergencyContact::create([
            'employee_id' => $emp->id,
            'name' => $data['name'],
            'relationship' => $data['relationship'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'is_primary' => $isPrimary,
        ]);

        return response()->json([
            'message' => 'Emergency contact added.',
            'contact' => new EmergencyContactResource($ec),
        ]);
    }

    public function updateEmergencyContact(Request $request, int $contact): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $ec = EmergencyContact::where('employee_id', $emp->id)->findOrFail($contact);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:200'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $isPrimary = (bool) ($data['is_primary'] ?? false);

        if ($isPrimary && ! $ec->is_primary) {
            EmergencyContact::where('employee_id', $emp->id)->where('id', '!=', $ec->id)->update(['is_primary' => false]);
        }

        $ec->update([
            'name' => $data['name'],
            'relationship' => $data['relationship'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'is_primary' => $isPrimary,
        ]);

        return response()->json(['message' => 'Emergency contact updated.', 'contact' => new EmergencyContactResource($ec)]);
    }

    public function destroyEmergencyContact(Request $request, int $contact): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $ec = EmergencyContact::where('employee_id', $emp->id)->findOrFail($contact);

        $wasPrimary = $ec->is_primary;
        $ec->delete();

        if ($wasPrimary) {
            $next = EmergencyContact::where('employee_id', $emp->id)->orderBy('id')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return response()->json(['message' => 'Emergency contact removed.']);
    }

    protected function employeeOrFail(Request $request)
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return $emp;
    }
}
