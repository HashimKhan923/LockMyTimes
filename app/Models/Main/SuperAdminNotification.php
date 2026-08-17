<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminNotification extends Model
{
    protected $connection = 'main';

    protected $fillable = [
        'super_admin_id', 'type', 'title', 'icon', 'color', 'action_url', 'data', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /** Scope: unread notifications for a given super admin */
    public function scopeUnreadFor($query, int $superAdminId)
    {
        return $query->where('super_admin_id', $superAdminId)->whereNull('read_at');
    }

    /** Scope: all notifications for a given super admin */
    public function scopeForAdmin($query, int $superAdminId)
    {
        return $query->where('super_admin_id', $superAdminId);
    }
}
