<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'task_list_id', 'parent_task_id',
        'milestone_id', 'created_by',
        'task_code', 'title', 'description',
        'type', 'status', 'priority', 'progress',
        'start_date', 'due_date', 'completed_at',
        'estimated_hours', 'logged_hours', 'billable_hours',
        'is_billable', 'hourly_rate',
        'sort_order', 'is_recurring', 'recurrence_rule', 'custom_fields',
        'comments_count', 'attachments_count',
        'subtasks_count', 'completed_subtasks_count',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'due_date'        => 'datetime',
        'completed_at'    => 'datetime',
        'is_billable'     => 'boolean',
        'is_recurring'    => 'boolean',
        'recurrence_rule' => 'array',
        'custom_fields'   => 'array',
        'estimated_hours' => 'float',
        'logged_hours'    => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($task) {
            if (empty($task->task_code)) {
                $count = static::withTrashed()->count() + 1;
                $task->task_code = 'TSK-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project()    { return $this->belongsTo(Project::class); }
    public function taskList()   { return $this->belongsTo(TaskList::class); }
    public function parent()     { return $this->belongsTo(Task::class, 'parent_task_id'); }
    public function subtasks()   { return $this->hasMany(Task::class, 'parent_task_id'); }
    public function assignees()  { return $this->hasMany(TaskAssignee::class); }
    public function comments()   { return $this->hasMany(TaskComment::class)->latest(); }
    public function checklists() { return $this->hasMany(TaskChecklist::class)->orderBy('sort_order'); }
    public function attachments(){ return $this->hasMany(TaskAttachment::class); }
    public function activities() { return $this->hasMany(TaskActivity::class)->latest(); }
    public function watchers()   { return $this->hasMany(TaskWatcher::class); }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['done','cancelled'])
            ->where('due_date', '<', now());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, ['done','cancelled']);
    }
}