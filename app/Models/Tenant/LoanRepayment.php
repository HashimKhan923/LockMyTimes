<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends TenantModel
{
    protected $table = 'loan_repayments';

    protected $fillable = [
        'loan_id', 'installment_number',
        'due_date',
        'principal_component', 'interest_component',
        'emi_amount', 'balance_after',
        'paid_date', 'amount_paid', 'late_fee',
        'status', 'payment_source',
        'payslip_id', 'reference', 'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date'             => 'date',
            'paid_date'            => 'date',
            'principal_component'  => 'decimal:2',
            'interest_component'   => 'decimal:2',
            'emi_amount'           => 'decimal:2',
            'balance_after'        => 'decimal:2',
            'amount_paid'          => 'decimal:2',
            'late_fee'             => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo     { return $this->belongsTo(Loan::class); }
    public function payslip(): BelongsTo  { return $this->belongsTo(Payslip::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }
}