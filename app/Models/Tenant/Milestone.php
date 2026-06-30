<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends TenantModel
{
    protected $fillable = [
        'project_id', 'title', 'description',
        'due_date', 'completed_date',
        'status', 'progress',
        'payment_amount', 'is_paid',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date'         => 'date',
            'completed_date'   => 'date',
            'payment_amount'   => 'decimal:2',
            'is_paid'          => 'boolean',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function tasks(): HasMany     { return $this->hasMany(Task::class); }
}