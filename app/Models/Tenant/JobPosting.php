<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends TenantModel
{
    protected $fillable = [
        'department_id', 'position_id', 'location_id',
        'title', 'slug', 'description',
        'requirements', 'benefits',
        'employment_type', 'work_mode', 'experience_level',
        'salary_min', 'salary_max', 'show_salary',
        'openings',
        'posted_date', 'closing_date', 'status',
        'views_count', 'applications_count',
        'hiring_manager_id', 'custom_questions',
    ];

    protected function casts(): array
    {
        return [
            'posted_date'      => 'date',
            'closing_date'     => 'date',
            'show_salary'      => 'boolean',
            'salary_min'       => 'decimal:2',
            'salary_max'       => 'decimal:2',
            'custom_questions' => 'array',
        ];
    }

    public function department(): BelongsTo    { return $this->belongsTo(Department::class); }
    public function position(): BelongsTo      { return $this->belongsTo(Position::class); }
    public function location(): BelongsTo      { return $this->belongsTo(Location::class); }
    public function hiringManager(): BelongsTo { return $this->belongsTo(Employee::class, 'hiring_manager_id'); }
    public function candidates(): HasMany      { return $this->hasMany(Candidate::class); }
}