<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends TenantModel
{
    protected $fillable = [
        'title', 'code', 'description',
        'type', 'category', 'provider', 'instructor',
        'start_date', 'end_date', 'start_time', 'end_time',
        'location', 'online_url',
        'cost', 'duration_hours', 'max_participants',
        'is_mandatory', 'issues_certificate', 'certificate_valid_months',
        'thumbnail', 'materials', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date'         => 'date',
            'end_date'           => 'date',
            'cost'               => 'decimal:2',
            'is_mandatory'       => 'boolean',
            'issues_certificate' => 'boolean',
            'materials'          => 'array',
            'is_active'          => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }
}