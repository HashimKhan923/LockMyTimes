<?php

namespace App\Models\Tenant;

class TaxSetting extends TenantModel
{
    protected $table = 'tax_settings';

    protected $fillable = [
        'year', 'state', 'tax_type',
        'brackets', 'flat_rate', 'wage_base', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'brackets'  => 'array',
            'flat_rate' => 'decimal:4',
            'wage_base' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}