<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignee extends TenantModel
{
    protected $table = 'task_assignees';

    protected $fillable = [
        'task_id', 'employee_id', 'assigned_by', 'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function task(): BelongsTo     { return $this->belongsTo(Task::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function assigner(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}