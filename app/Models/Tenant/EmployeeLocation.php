<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLocation extends TenantModel
{
    protected $fillable = ['employee_id', 'location_id', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
}
