<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends TenantModel
{
    protected $table = 'project_members';

    protected $fillable = [
        'project_id', 'employee_id', 'role',
        'hourly_rate', 'can_log_time', 'can_manage_tasks',
        'is_billable', 'joined_at', 'left_at',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate'      => 'decimal:2',
            'can_log_time'     => 'boolean',
            'can_manage_tasks' => 'boolean',
            'is_billable'      => 'boolean',
            'joined_at'        => 'datetime',
            'left_at'          => 'datetime',
        ];
    }

    public function project(): BelongsTo  { return $this->belongsTo(Project::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}