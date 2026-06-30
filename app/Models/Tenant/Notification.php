<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Notification extends TenantModel
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id', 'type', 'notifiable_type', 'notifiable_id',
        'data', 'title', 'icon', 'color', 'action_url', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
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

    /** Scope: unread notifications for a given user */
    public function scopeUnreadFor($query, int $userId)
    {
        return $query->where('notifiable_type', User::class)
                     ->where('notifiable_id', $userId)
                     ->whereNull('read_at');
    }

    /** Scope: all notifications for a given user */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('notifiable_type', User::class)
                     ->where('notifiable_id', $userId);
    }
}
