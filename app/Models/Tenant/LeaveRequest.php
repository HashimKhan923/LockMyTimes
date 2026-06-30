<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends TenantModel
{
    protected $fillable = [
        'employee_id', 'leave_type_id', 'request_number',
        'start_date', 'end_date', 'total_days', 'day_part',
        'reason', 'contact_during_leave', 'attachments',
        'status', 'approved_by', 'approved_at',
        'approver_comments', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'total_days'  => 'decimal:2',
            'attachments' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }

    public static function generateNumber(): string
    {
        return 'LR-'.date('Y').'-'.str_pad((string) (static::max('id') + 1), 6, '0', STR_PAD_LEFT);
    }
}