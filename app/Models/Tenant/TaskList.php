<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskList extends TenantModel
{
    protected $table = 'task_lists';

    protected $fillable = [
        'project_id', 'name', 'color',
        'column_type', 'sort_order',
        'wip_limit', 'is_closed_status',
    ];

    protected function casts(): array
    {
        return ['is_closed_status' => 'boolean'];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function tasks(): HasMany     { return $this->hasMany(Task::class); }
}