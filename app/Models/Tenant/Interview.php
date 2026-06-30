<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends TenantModel
{
    protected $fillable = [
        'candidate_id', 'title', 'type',
        'scheduled_at', 'duration_minutes',
        'meeting_url', 'location',
        'interviewer_ids', 'status',
        'rating', 'feedback', 'recommendation',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'    => 'datetime',
            'interviewer_ids' => 'array',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}