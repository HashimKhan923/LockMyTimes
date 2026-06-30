<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignment extends TenantModel
{
    protected $fillable = [
        'employee_id', 'shift_id',
        'start_date', 'end_date',
        'is_default', 'overrides',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_default' => 'boolean',
            'overrides'  => 'array',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function shift(): BelongsTo    { return $this->belongsTo(Shift::class); }
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', today())
                    ->where(fn($q) => $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', today()));
    }
}