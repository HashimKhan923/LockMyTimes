<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryComponent extends TenantModel
{
    protected $table = 'employee_salary_components';

    protected $fillable = [
        'employee_id', 'salary_component_id',
        'amount', 'effective_from', 'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'effective_from' => 'date',
            'effective_to'   => 'date',
        ];
    }

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function component(): BelongsTo { return $this->belongsTo(SalaryComponent::class, 'salary_component_id'); }
}