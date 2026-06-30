<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends TenantModel
{
    protected $fillable = [
        'name', 'code', 'start_time', 'end_time',
        'break_minutes', 'crosses_midnight', 'total_hours',
        'color', 'late_grace_minutes', 'early_out_grace_minutes',
        'working_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'crosses_midnight' => 'boolean',
            'total_hours'      => 'decimal:2',
            'working_days'     => 'array',
            'is_active'        => 'boolean',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }
}