<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OneOnOne extends TenantModel
{
    protected $table = 'one_on_ones';

    protected $fillable = [
        'employee_id', 'manager_id',
        'scheduled_at', 'duration_minutes',
        'agenda', 'notes', 'action_items', 'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'agenda'       => 'array',
            'action_items' => 'array',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function manager(): BelongsTo  { return $this->belongsTo(Employee::class, 'manager_id'); }
}