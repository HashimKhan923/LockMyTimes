<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends TenantModel
{
    protected $fillable = [
        'employee_id', 'leave_type_id', 'year',
        'allocated', 'accrued', 'used', 'pending',
        'carried_over', 'adjusted',
    ];

    protected function casts(): array
    {
        return [
            'allocated'    => 'decimal:2',
            'accrued'      => 'decimal:2',
            'used'         => 'decimal:2',
            'pending'      => 'decimal:2',
            'carried_over' => 'decimal:2',
            'adjusted'     => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }

    public function getAvailableAttribute(): float
    {
        return (float) ($this->allocated + $this->accrued + $this->carried_over + $this->adjusted - $this->used - $this->pending);
    }
}