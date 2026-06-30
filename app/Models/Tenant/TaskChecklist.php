<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklist extends TenantModel
{
    protected $table = 'task_checklists';

    protected $fillable = [
        'task_id', 'item',
        'is_completed', 'completed_by',
        'completed_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo      { return $this->belongsTo(Task::class); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}