<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends TenantModel
{
    protected $fillable = [
        'name', 'date', 'type', 'state', 'location_id',
        'is_recurring', 'is_paid', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'is_recurring' => 'boolean',
            'is_paid'      => 'boolean',
            'is_active'    => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** Holidays visible to an employee: tenant-wide ones (location_id null) plus their own location(s). */
    public function scopeVisibleTo($query, array $locationIds)
    {
        return $query->where(function ($q) use ($locationIds) {
            $q->whereNull('location_id');
            if (! empty($locationIds)) {
                $q->orWhereIn('location_id', $locationIds);
            }
        });
    }
}