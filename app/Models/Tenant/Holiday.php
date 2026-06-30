<?php

namespace App\Models\Tenant;

class Holiday extends TenantModel
{
    protected $fillable = [
        'name', 'date', 'type', 'state',
        'is_recurring', 'is_paid', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'is_recurring' => 'boolean',
            'is_paid'      => 'boolean',
            'is_active'    => 'boolean',
        ];
    }
}