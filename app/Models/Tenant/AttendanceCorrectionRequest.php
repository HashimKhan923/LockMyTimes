<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionRequest extends TenantModel
{
    protected $fillable = [
        'employee_id', 'request_number', 'work_date',
        'proposed_clock_in', 'proposed_clock_out', 'reason',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'work_date'   => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateNumber(): string
    {
        return 'AC-'.date('Y').'-'.str_pad((string) (static::max('id') + 1), 6, '0', STR_PAD_LEFT);
    }
}
