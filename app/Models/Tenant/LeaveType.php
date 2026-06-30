<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends TenantModel
{
    protected $fillable = [
        'name', 'code', 'description', 'color', 'is_paid',
        'default_days_per_year', 'max_carryover_days',
        'accrual_method', 'accrual_rate',
        'min_service_months', 'requires_approval', 'requires_documentation',
        'max_consecutive_days', 'notice_days_required', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_paid'                => 'boolean',
            'requires_approval'      => 'boolean',
            'requires_documentation' => 'boolean',
            'is_active'              => 'boolean',
            'default_days_per_year'  => 'decimal:2',
            'max_carryover_days'     => 'decimal:2',
            'accrual_rate'           => 'decimal:4',
        ];
    }

    public function requests(): HasMany  { return $this->hasMany(LeaveRequest::class); }
    public function balances(): HasMany  { return $this->hasMany(LeaveBalance::class); }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}