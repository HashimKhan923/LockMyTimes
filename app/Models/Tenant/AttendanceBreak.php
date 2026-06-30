<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceBreak extends TenantModel
{
    protected $table = 'attendance_breaks';

    protected $fillable = [
        'attendance_id', 'start_at', 'end_at',
        'duration_minutes', 'break_type', 'is_paid', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_at'         => 'datetime',
            'end_at'           => 'datetime',
            'duration_minutes' => 'decimal:2',
            'is_paid'          => 'boolean',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}