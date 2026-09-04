<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryComponent extends TenantModel
{
    protected $table = 'employee_salary_components';

    protected $fillable = [
        'employee_id', 'salary_component_id',
        'amount', 'effective_from', 'effective_to', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'is_active'      => 'boolean',
        ];
    }

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function component(): BelongsTo { return $this->belongsTo(SalaryComponent::class, 'salary_component_id'); }

    /** Currently in effect: active, and within its effective date window (if any). */
    public function scopeCurrentlyActive($query)
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $today))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today));
    }
}