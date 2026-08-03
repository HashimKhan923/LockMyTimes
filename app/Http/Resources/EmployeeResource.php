<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'full_name' => $this->full_name,
            'preferred_name' => $this->preferred_name,
            'avatar_url' => $this->avatar_url,
            'email' => $this->email,
            'personal_email' => $this->personal_email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'nationality' => $this->nationality,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'department' => $this->whenLoaded('department', fn () => $this->department?->name),
            'position' => $this->whenLoaded('position', fn () => $this->position?->title),
            'location' => $this->whenLoaded('location', fn () => $this->location?->name),
            'manager_name' => $this->whenLoaded('manager', fn () => $this->manager?->full_name),
            'hire_date' => $this->hire_date?->toDateString(),
            'probation_end_date' => $this->probation_end_date?->toDateString(),
            'confirmation_date' => $this->confirmation_date?->toDateString(),
            'employment_status' => $this->employment_status,
            'employment_type' => $this->employment_type,
            'privacy_settings' => $this->privacy_settings ?? \App\Models\Tenant\Employee::defaultPrivacySettings(),
        ];
    }
}
