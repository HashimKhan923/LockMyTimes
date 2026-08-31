<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends TenantModel
{
    protected $table = 'asset_assignments';

    protected $fillable = [
        'asset_id', 'employee_id', 'assigned_by',
        'assigned_at', 'returned_at',
        'condition_at_assignment', 'condition_at_return',
        'assignment_notes', 'return_notes',
        'handover_document',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo     { return $this->belongsTo(Asset::class); }
    public function employee(): BelongsTo  { return $this->belongsTo(Employee::class); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}