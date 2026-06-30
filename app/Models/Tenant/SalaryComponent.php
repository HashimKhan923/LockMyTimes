<?php

namespace App\Models\Tenant;

class SalaryComponent extends TenantModel
{
    protected $fillable = [
        'name', 'code', 'type', 'calculation',
        'default_value', 'is_taxable', 'is_recurring',
        'shows_on_payslip', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_value'    => 'decimal:2',
            'is_taxable'       => 'boolean',
            'is_recurring'     => 'boolean',
            'shows_on_payslip' => 'boolean',
            'is_active'        => 'boolean',
        ];
    }

    public function employeeSalaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }
}