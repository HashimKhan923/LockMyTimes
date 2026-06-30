<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'department_id', 'project_manager_id',
        'code', 'name', 'slug', 'description',
        'color', 'icon', 'cover_image',
        'type', 'visibility', 'is_billable',
        'status', 'priority', 'progress',
        'start_date', 'due_date', 'actual_start_date', 'actual_end_date',
        'budget_amount', 'budget_currency', 'budget_hours',
        'hourly_rate', 'fixed_price', 'billing_type',
        'total_logged_hours', 'total_billable_hours', 'total_cost',
        'tasks_count', 'completed_tasks_count', 'members_count',
        'custom_fields', 'settings', 'tags', 'notes',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'due_date'         => 'date',
        'actual_start_date'=> 'date',
        'actual_end_date'  => 'date',
        'is_billable'      => 'boolean',
        'custom_fields'    => 'array',
        'settings'         => 'array',
        'tags'             => 'array',
        'budget_amount'    => 'float',
        'total_logged_hours'=> 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name) . '-' . Str::random(4);
            }
            if (empty($project->code)) {
                $count = static::count() + 1;
                $project->code = 'PRJ-' . now()->format('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function manager()      { return $this->belongsTo(Employee::class, 'project_manager_id'); }
    public function department()   { return $this->belongsTo(Department::class); }
    public function members()      { return $this->hasMany(ProjectMember::class); }
    public function taskLists()    { return $this->hasMany(TaskList::class)->orderBy('sort_order'); }
    public function tasks()        { return $this->hasMany(Task::class); }
    public function milestones()   { return $this->hasMany(Milestone::class)->orderBy('due_date'); }
    public function documents()    { return $this->hasMany(ProjectDocument::class); }

    public function scopeActive($query) { return $query->where('status', 'active'); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && ! in_array($this->status, ['completed','cancelled','archived']);
    }
}