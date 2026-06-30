<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryAdvance extends TenantModel
{
    use SoftDeletes;

    protected $table = 'salary_advances';

    protected $fillable = [
        'employee_id', 'advance_number',
        'amount', 'reason', 'supporting_document',
        'repayment_type', 'installments_count',
        'per_installment_amount', 'first_deduction_date',
        'amount_repaid', 'amount_remaining', 'installments_paid',
        'status',
        'approved_by', 'approved_at',
        'disbursed_by', 'disbursed_at',
        'completed_at',
        'rejection_reason', 'approver_comments', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'                 => 'decimal:2',
            'per_installment_amount' => 'decimal:2',
            'amount_repaid'          => 'decimal:2',
            'amount_remaining'       => 'decimal:2',
            'first_deduction_date'   => 'date',
            'approved_at'            => 'datetime',
            'disbursed_at'           => 'datetime',
            'completed_at'           => 'datetime',
        ];
    }

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }
    public function disburser(): BelongsTo { return $this->belongsTo(User::class, 'disbursed_by'); }
    public function deductions(): HasMany  { return $this->hasMany(AdvanceDeduction::class); }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'ADV-'.$year.'-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}