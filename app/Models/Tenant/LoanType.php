<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends TenantModel
{
    protected $table = 'loan_types';

    protected $fillable = [
        'name', 'code', 'description', 'icon', 'color',
        'default_interest_rate', 'interest_type',
        'max_amount', 'min_amount',
        'max_tenure_months', 'min_tenure_months',
        'min_service_months', 'min_salary', 'max_salary_multiplier',
        'cooldown_months',
        'requires_guarantor', 'requires_documentation', 'requires_collateral',
        'auto_deduct_from_payroll', 'allow_early_repayment',
        'early_repayment_fee_percent', 'late_payment_fee_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_interest_rate'      => 'decimal:2',
            'max_amount'                 => 'decimal:2',
            'min_amount'                 => 'decimal:2',
            'min_salary'                 => 'decimal:2',
            'max_salary_multiplier'      => 'decimal:2',
            'early_repayment_fee_percent'=> 'decimal:2',
            'late_payment_fee_percent'   => 'decimal:2',
            'requires_guarantor'         => 'boolean',
            'requires_documentation'     => 'boolean',
            'requires_collateral'        => 'boolean',
            'auto_deduct_from_payroll'   => 'boolean',
            'allow_early_repayment'      => 'boolean',
            'is_active'                  => 'boolean',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}