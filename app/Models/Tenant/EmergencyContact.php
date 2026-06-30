<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends TenantModel
{
    protected $table = 'employee_emergency_contacts';

    protected $fillable = [
        'employee_id', 'name', 'relationship', 'phone', 'email', 'address', 'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}