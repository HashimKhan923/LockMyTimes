<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseApproval extends TenantModel
{
    protected $table = 'expense_approvals';

    protected $fillable = [
        'expense_id', 'approver_id',
        'approval_level', 'decision',
        'comments', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function expense(): BelongsTo  { return $this->belongsTo(Expense::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }
}