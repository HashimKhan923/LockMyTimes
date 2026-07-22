<?php

namespace App\Http\Resources;

use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'locale' => $this->locale ?? 'en',
            'timezone' => $this->timezone,
            'date_format' => $this->date_format ?? 'DMY',
            'time_format' => $this->time_format ?? '12',
            'theme' => $this->theme ?? 'system',
            'must_change_password' => (bool) $this->must_change_password,
            'notification_preferences' => $this->notification_preferences ?? User::defaultNotificationPreferences(),
        ];
    }
}
