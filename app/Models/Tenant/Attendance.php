<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends TenantModel
{
    protected $fillable = [
        'employee_id', 'location_id', 'qr_code_id',
        'work_date', 'clock_in_at', 'clock_out_at',
        'clock_in_lat', 'clock_in_lng', 'clock_out_lat', 'clock_out_lng',
        'clock_in_distance_meters', 'clock_out_distance_meters',
        'clock_in_selfie', 'clock_out_selfie',
        'clock_in_ip', 'clock_out_ip',
        'device_fingerprint', 'user_agent',
        'total_hours', 'break_hours', 'overtime_hours', 'regular_hours',
        'is_late', 'late_minutes', 'is_early_out', 'early_minutes',
        'source', 'status',
        'is_geofence_breach', 'is_manual_entry', 'is_approved',
        'is_remote_clockin', 'clock_in_city', 'clock_in_country', 'clock_in_timezone',
        'approved_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date'          => 'date',
            'clock_in_at'        => 'datetime',
            'clock_out_at'       => 'datetime',
            'clock_in_lat'       => 'decimal:7',
            'clock_in_lng'       => 'decimal:7',
            'clock_out_lat'      => 'decimal:7',
            'clock_out_lng'      => 'decimal:7',
            'total_hours'        => 'decimal:2',
            'break_hours'        => 'decimal:2',
            'overtime_hours'     => 'decimal:2',
            'regular_hours'      => 'decimal:2',
            'is_late'            => 'boolean',
            'is_early_out'       => 'boolean',
            'is_geofence_breach' => 'boolean',
            'is_manual_entry'    => 'boolean',
            'is_approved'        => 'boolean',
            'is_remote_clockin'  => 'boolean',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function qrCode(): BelongsTo   { return $this->belongsTo(QrCode::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function breaks(): HasMany     { return $this->hasMany(AttendanceBreak::class); }

}