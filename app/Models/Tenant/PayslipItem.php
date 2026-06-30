<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipItem extends TenantModel
{
    protected $fillable = [
        'payslip_id', 'salary_component_id',
        'label', 'type', 'amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function payslip(): BelongsTo  { return $this->belongsTo(Payslip::class); }
    public function component(): BelongsTo{ return $this->belongsTo(SalaryComponent::class, 'salary_component_id'); }
}