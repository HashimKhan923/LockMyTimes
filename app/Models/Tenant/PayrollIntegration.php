<?php

namespace App\Models\Tenant;

class PayrollIntegration extends TenantModel
{
    protected $fillable = [
        'provider', 'api_key', 'base_url', 'status',
        'last_synced_at', 'last_error', 'employees_synced', 'payslips_synced',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected' && ! empty($this->api_key);
    }
}
