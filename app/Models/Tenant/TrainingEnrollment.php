<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends TenantModel
{
    protected $table = 'training_enrollments';

    protected $fillable = [
        'training_id', 'employee_id',
        'enrolled_at', 'status', 'progress', 'score',
        'completed_at', 'certificate_path', 'certificate_expiry',
        'feedback', 'rating',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at'        => 'datetime',
            'completed_at'       => 'datetime',
            'certificate_expiry' => 'date',
            'score'              => 'decimal:2',
        ];
    }

    public function training(): BelongsTo { return $this->belongsTo(Training::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}