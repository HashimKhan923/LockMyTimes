<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends TenantModel
{
    protected $fillable = [
        'run_number', 'pay_schedule',
        'external_source', 'external_id',
        'period_start', 'period_end', 'pay_date',
        'status',
        'total_employees', 'total_gross', 'total_deductions', 'total_taxes', 'total_net',
        'created_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start'     => 'date',
            'period_end'       => 'date',
            'pay_date'         => 'date',
            'approved_at'      => 'datetime',
            'total_gross'      => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_taxes'      => 'decimal:2',
            'total_net'        => 'decimal:2',
        ];
    }

    public function payslips(): HasMany { return $this->hasMany(Payslip::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}