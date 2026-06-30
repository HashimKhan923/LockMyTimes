<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends TenantModel
{
    protected $table = 'task_dependencies';

    protected $fillable = ['task_id', 'depends_on_task_id', 'type'];

    public function task(): BelongsTo       { return $this->belongsTo(Task::class); }
    public function dependsOn(): BelongsTo  { return $this->belongsTo(Task::class, 'depends_on_task_id'); }
}