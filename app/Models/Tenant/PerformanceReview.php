<?php

namespace App\Models\Tenant;

class PerformanceReview extends TenantModel
{
    protected $fillable = [
        'review_cycle_id', 'employee_id', 'reviewer_id',
        'review_type', 'status',
        'overall_rating', 'competency_ratings',
        'strengths', 'areas_for_improvement',
        'manager_comments', 'employee_comments',
        'due_date', 'submitted_at', 'acknowledged_at',
        'reviewed_by', 'completed_at',
        'achievements', 'goals_next_cycle', 'comments',
    ];

    protected $casts = [
        'due_date'          => 'date',
        'submitted_at'      => 'datetime',
        'acknowledged_at'   => 'datetime',
        'completed_at'      => 'datetime',
        'overall_rating'    => 'float',
        'competency_ratings'=> 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function cycle()
    {
        return $this->belongsTo(ReviewCycle::class, 'review_cycle_id');
    }

    public function responses()
    {
        return $this->hasMany(ReviewResponse::class);
    }

    public function getRatingLabelAttribute(): string
    {
        return match(true) {
            $this->overall_rating >= 4.5 => 'Outstanding',
            $this->overall_rating >= 3.5 => 'Exceeds Expectations',
            $this->overall_rating >= 2.5 => 'Meets Expectations',
            $this->overall_rating >= 1.5 => 'Needs Improvement',
            default                       => 'Unsatisfactory',
        };
    }

    public function getRatingColorAttribute(): string
    {
        return match(true) {
            $this->overall_rating >= 4.5 => 'text-emerald-600',
            $this->overall_rating >= 3.5 => 'text-brand-600',
            $this->overall_rating >= 2.5 => 'text-amber-600',
            default                       => 'text-red-500',
        };
    }
}