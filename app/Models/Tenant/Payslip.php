<?php

namespace App\Models\Tenant;

class Payslip extends TenantModel
{
    protected $fillable = [
        'payroll_run_id', 'employee_id', 'payslip_number',
        'external_source', 'external_id',
        'period_start', 'period_end', 'pay_date',
        'regular_hours', 'overtime_hours', 'holiday_hours', 'leave_hours',
        'base_pay', 'overtime_pay', 'bonus', 'commission', 'reimbursement',
        'gross_pay',
        'federal_tax', 'state_tax', 'local_tax', 'fica_ss', 'fica_medicare',
        'health_insurance', 'retirement_401k', 'other_deductions', 'total_deductions',
        'net_pay',
        'ytd_gross', 'ytd_net', 'ytd_taxes',
        'status', 'payment_method', 'pdf_path', 'is_emailed', 'emailed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'pay_date'     => 'date',
        'emailed_at'   => 'datetime',
        'is_emailed'   => 'boolean',
        'gross_pay'    => 'float',
        'net_pay'      => 'float',
        'base_pay'     => 'float',
        'federal_tax'  => 'float',
        'fica_ss'      => 'float',
        'fica_medicare'=> 'float',
        'total_deductions' => 'float',
        'overtime_pay' => 'float',
        'overtime_hours'=> 'float',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function items()
    {
        return $this->hasMany(PayslipItem::class)->orderBy('sort_order');
    }

    /* Computed helpers for views */
    public function getTaxAmountAttribute(): float
    {
        return round(
            (float)$this->federal_tax +
            (float)$this->state_tax +
            (float)$this->fica_ss +
            (float)$this->fica_medicare, 2
        );
    }

    public function getGrossSalaryAttribute(): float { return (float) $this->gross_pay; }
    public function getNetSalaryAttribute(): float   { return (float) $this->net_pay; }
    public function getBaseSalaryAttribute(): float  { return (float) $this->base_pay; }
}