<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QrCode extends TenantModel
{
    protected $table = 'qr_codes';

    protected $fillable = [
        'location_id', 'token', 'label',
        'is_active', 'require_selfie', 'rotate_token',
        'last_rotated_at', 'scan_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'require_selfie'  => 'boolean',
            'rotate_token'    => 'boolean',
            'last_rotated_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public static function generateToken(): string
    {
        return Str::random(48);
    }



    public function getQrDataUriAttribute(): string
    {
        return app(\App\Services\QrCodeService::class)->generateDataUri($this->token, 200);
    }
}