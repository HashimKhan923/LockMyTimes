<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceDeduction extends TenantModel
{
    protected $table = 'advance_deductions';

    protected $fillable = [
        'salary_advance_id', 'payslip_id',
        'deduction_number', 'deduction_date',
        'amount', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'deduction_date' => 'date',
            'amount'         => 'decimal:2',
        ];
    }

    public function advance(): BelongsTo { return $this->belongsTo(SalaryAdvance::class, 'salary_advance_id'); }
    public function payslip(): BelongsTo { return $this->belongsTo(Payslip::class); }
}