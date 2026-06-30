<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends TenantModel
{
    protected $fillable = [
        'created_by', 'title', 'content',
        'priority', 'audience', 'audience_filter',
        'banner_image',
        'requires_acknowledgment', 'show_on_login',
        'publish_at', 'expires_at', 'status', 'views_count',
    ];

    protected function casts(): array
    {
        return [
            'audience_filter'         => 'array',
            'requires_acknowledgment' => 'boolean',
            'show_on_login'           => 'boolean',
            'publish_at'              => 'datetime',
            'expires_at'              => 'datetime',
        ];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reads(): HasMany     { return $this->hasMany(AnnouncementRead::class); }
}