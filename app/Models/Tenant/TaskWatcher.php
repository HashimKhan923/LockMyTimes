<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskWatcher extends TenantModel
{
    protected $table = 'task_watchers';

    protected $fillable = ['task_id', 'employee_id'];

    public function task(): BelongsTo     { return $this->belongsTo(Task::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}