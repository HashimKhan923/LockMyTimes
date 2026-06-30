<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends TenantModel
{
    protected $table = 'shift_swap_requests';

    protected $fillable = [
        'requester_id', 'requestee_id',
        'requester_shift_date', 'requestee_shift_date',
        'reason', 'status', 'approved_by', 'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'requester_shift_date' => 'date',
            'requestee_shift_date' => 'date',
            'responded_at'         => 'datetime',
        ];
    }

    public function requester(): BelongsTo { return $this->belongsTo(Employee::class, 'requester_id'); }
    public function requestee(): BelongsTo { return $this->belongsTo(Employee::class, 'requestee_id'); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }
}