<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends TenantModel
{
    protected $fillable = [
        'job_posting_id',
        'first_name', 'last_name', 'email', 'phone', 'address',
        'linkedin_url', 'portfolio_url',
        'resume_path', 'cover_letter_path', 'cover_letter_text',
        'custom_answers', 'expected_salary', 'available_from',
        'source', 'referred_by',
        'stage', 'rating', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'custom_answers'   => 'array',
            'expected_salary'  => 'decimal:2',
            'available_from'   => 'date',
        ];
    }

    public function jobPosting(): BelongsTo   { return $this->belongsTo(JobPosting::class); }
    public function referrer(): BelongsTo     { return $this->belongsTo(Employee::class, 'referred_by'); }
    public function interviews(): HasMany     { return $this->hasMany(Interview::class); }
    public function stageHistory(): HasMany   { return $this->hasMany(CandidateStageHistory::class); }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}