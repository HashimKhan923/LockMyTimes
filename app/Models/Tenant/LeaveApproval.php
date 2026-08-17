<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends TenantModel
{
    protected $table = 'leave_approvals';

    protected $fillable = [
        'leave_request_id', 'approver_id',
        'approval_level', 'decision',
        'comments', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function leaveRequest(): BelongsTo { return $this->belongsTo(LeaveRequest::class); }
    public function approver(): BelongsTo     { return $this->belongsTo(User::class, 'approver_id'); }
}
