<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewResponse extends TenantModel
{
    protected $fillable = [
        'review_id', 'question', 'question_type',
        'answer', 'rating', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['rating' => 'decimal:2'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'review_id');
    }
}