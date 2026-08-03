<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Always run on the dynamically-switched "tenant" connection.
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'is_active',
        'must_change_password',
        'locale',
        'timezone',
        'device_token',
        'notification_preferences',
        'date_format',
        'time_format',
        'theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'last_login_at'        => 'datetime',
            'password'             => 'hashed',
            'is_active'                 => 'boolean',
            'must_change_password'      => 'boolean',
            'notification_preferences'  => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function defaultNotificationPreferences(): array
    {
        return [
            'leave_approvals'    => ['in_app' => true,  'email' => true],
            'payslip_available'  => ['in_app' => true,  'email' => true],
            'announcements'      => ['in_app' => true,  'email' => false],
            'expense_updates'    => ['in_app' => true,  'email' => true],
            'loan_updates'       => ['in_app' => true,  'email' => false],
            'task_assignments'   => ['in_app' => true,  'email' => false],
        ];
    }

    public function getNotifPref(string $key, string $channel): bool
    {
        $prefs = $this->notification_preferences ?? [];
        return (bool) ($prefs[$key][$channel] ?? self::defaultNotificationPreferences()[$key][$channel] ?? false);
    }

    public function getAvatarUrlAttribute(): string
    {
        // Employee-facing avatar uploads (admin employee edit, mobile profile
        // screen) write to employees.avatar, not users.avatar — prefer that
        // so the logged-in user's avatar matches what their employee profile
        // shows, rather than falling back to a placeholder despite a real
        // photo being on file.
        $path = $this->avatar ?: $this->employee?->avatar;

        return $path
            ? asset('storage/'.$path)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=6C7DF7&color=fff';
    }
}