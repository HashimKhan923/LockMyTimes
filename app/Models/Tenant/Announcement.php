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

    /**
     * Resolve which active users this announcement's audience/audience_filter
     * actually targets — the inverse of the per-viewer visibility check each
     * employee-facing controller runs (visibleAnnouncementsQuery()). Used to
     * decide who gets notified (bell/push) and emailed when this announcement
     * is published, so both stay in sync with the same targeting rules.
     */
    public function targetUsers(): \Illuminate\Support\Collection
    {
        $query = User::where('is_active', true);
        $filterIds = collect($this->audience_filter ?? [])
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->values();

        return match ($this->audience) {
            'department' => $filterIds->isEmpty() ? collect() : $query->whereHas('employee',
                fn ($q) => $q->whereIn('department_id', $filterIds)
            )->get(),
            'location' => $filterIds->isEmpty() ? collect() : $query->whereHas('employee',
                fn ($q) => $q->whereIn('location_id', $filterIds)
            )->get(),
            'role' => empty($this->audience_filter) ? collect() : $query->role($this->audience_filter)->get(),
            'specific' => $filterIds->isEmpty() ? collect() : $query->whereIn('id', $filterIds)->get(),
            default => $query->get(), // 'all'
        };
    }
}