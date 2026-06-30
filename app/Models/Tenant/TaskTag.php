<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskTag extends TenantModel
{
    protected $table = 'task_tags';

    protected $fillable = ['project_id', 'name', 'color'];

    public function project(): BelongsTo  { return $this->belongsTo(Project::class); }
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_tag_pivot', 'task_tag_id', 'task_id');
    }
}