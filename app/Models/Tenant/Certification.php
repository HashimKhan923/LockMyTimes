<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends TenantModel
{
    protected $fillable = [
        'employee_id', 'name', 'issuer',
        'credential_id', 'credential_url',
        'issue_date', 'expiry_date',
        'certificate_file', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'  => 'date',
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}