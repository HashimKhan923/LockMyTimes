<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'loan_type_id',
        'loan_number',
        'principal_amount', 'interest_rate', 'interest_type',
        'total_interest', 'total_amount', 'processing_fee',
        'tenure_months', 'emi_amount', 'first_emi_date',
        'amount_paid', 'amount_remaining',
        'installments_paid', 'installments_remaining',
        'purpose', 'agreement_document', 'supporting_documents',
        'guarantor_employee_id', 'guarantor_external_name',
        'guarantor_external_phone', 'guarantor_document',
        'status',
        'requested_by', 'requested_at',
        'approved_by', 'approved_at',
        'disbursed_by', 'disbursed_at',
        'disbursement_method', 'disbursement_reference',
        'completed_at', 'rejection_reason', 'approver_comments', 'notes',
        'auto_deduct_from_payroll',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount'      => 'decimal:2',
            'interest_rate'         => 'decimal:2',
            'total_interest'        => 'decimal:2',
            'total_amount'          => 'decimal:2',
            'processing_fee'        => 'decimal:2',
            'emi_amount'            => 'decimal:2',
            'amount_paid'           => 'decimal:2',
            'amount_remaining'      => 'decimal:2',
            'first_emi_date'        => 'date',
            'supporting_documents'  => 'array',
            'requested_at'          => 'datetime',
            'approved_at'           => 'datetime',
            'disbursed_at'          => 'datetime',
            'completed_at'          => 'datetime',
            'auto_deduct_from_payroll' => 'boolean',
        ];
    }

    public function employee(): BelongsTo           { return $this->belongsTo(Employee::class); }
    public function loanType(): BelongsTo           { return $this->belongsTo(LoanType::class); }
    public function guarantorEmployee(): BelongsTo  { return $this->belongsTo(Employee::class, 'guarantor_employee_id'); }
    public function requester(): BelongsTo          { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver(): BelongsTo           { return $this->belongsTo(User::class, 'approved_by'); }
    public function disburser(): BelongsTo          { return $this->belongsTo(User::class, 'disbursed_by'); }
    public function repayments(): HasMany           { return $this->hasMany(LoanRepayment::class); }

    public function getNextRepaymentAttribute(): ?LoanRepayment
    {
        return $this->repayments()
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['disbursed', 'active']);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'LN-'.$year.'-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}