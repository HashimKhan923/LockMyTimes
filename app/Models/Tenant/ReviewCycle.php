<?php

namespace App\Models\Tenant;

class ReviewCycle extends TenantModel
{
    protected $fillable = [
        'name', 'type', 'description',
        'start_date', 'end_date', 'due_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'due_date'   => 'date',
    ];

    public function reviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}