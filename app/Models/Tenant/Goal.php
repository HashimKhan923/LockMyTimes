<?php

namespace App\Models\Tenant;

class Goal extends TenantModel
{
    protected $fillable = [
        'employee_id', 'parent_goal_id',
        'title', 'description',
        'type', 'category',
        'start_date', 'end_date',
        'progress', 'status', 'weight',
        'key_results',
        'priority',
        'target_value', 'current_value', 'unit',
        'set_by', 'completed_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'completed_at'=> 'datetime',
        'progress'    => 'integer',
        'weight'      => 'float',
        'key_results' => 'array',
        'target_value'=> 'float',
        'current_value'=> 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function parent()
    {
        return $this->belongsTo(Goal::class, 'parent_goal_id');
    }

    public function children()
    {
        return $this->hasMany(Goal::class, 'parent_goal_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active'
            && $this->end_date
            && $this->end_date->isPast();
    }
}