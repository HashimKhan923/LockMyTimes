<?php

namespace App\Models\Tenant;

class Expense extends TenantModel
{
    protected $fillable = [
        'employee_id', 'category_id',
        'expense_number', 'title', 'description',
        'amount', 'currency',
        'expense_date', 'merchant', 'payment_method',
        'project_code', 'receipt_path',
        'is_mileage', 'miles', 'mileage_rate',
        'status',
        'submitted_at', 'approved_at', 'paid_at',
        'paid_via_payslip_id', 'rejection_reason',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'approved_at'  => 'datetime',
        'paid_at'      => 'datetime',
        'submitted_at' => 'datetime',
        'amount'       => 'float',
        'is_mileage'   => 'boolean',
        'miles'        => 'float',
        'mileage_rate' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path
            ? asset('storage/' . $this->receipt_path)
            : null;
    }
}