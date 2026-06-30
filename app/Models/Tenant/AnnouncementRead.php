<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRead extends TenantModel
{
    protected $table = 'announcement_reads';

    protected $fillable = [
        'announcement_id', 'user_id',
        'read_at', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at'         => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo { return $this->belongsTo(Announcement::class); }
    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
}