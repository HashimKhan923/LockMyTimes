<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends TenantModel
{
    protected $table = 'task_activities';

    protected $fillable = [
        'task_id', 'user_id',
        'action', 'field',
        'old_value', 'new_value', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function task(): BelongsTo { return $this->belongsTo(Task::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Human-readable summary of this activity for timeline display.
     */
    public function getSummaryAttribute(): string
    {
        $name = $this->user?->name ?? 'System';

        return match ($this->action) {
            'created'         => "{$name} created this task",
            'status_changed'  => "{$name} changed status from <b>{$this->old_value}</b> to <b>{$this->new_value}</b>",
            'assigned'        => "{$name} assigned {$this->new_value}",
            'unassigned'      => "{$name} removed {$this->old_value}",
            'priority_changed'=> "{$name} changed priority to <b>{$this->new_value}</b>",
            'due_date_changed'=> "{$name} set due date to <b>{$this->new_value}</b>",
            'commented'       => "{$name} added a comment",
            'attachment_added'=> "{$name} attached a file",
            'completed'       => "{$name} marked task as complete",
            default           => "{$name} updated {$this->field}",
        };
    }
}