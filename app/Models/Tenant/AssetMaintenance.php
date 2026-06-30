<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends TenantModel
{
    protected $table = 'asset_maintenance';

    protected $fillable = [
        'asset_id', 'maintenance_date',
        'type', 'description',
        'cost', 'vendor', 'next_maintenance_date',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date'       => 'date',
            'next_maintenance_date'  => 'date',
            'cost'                   => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo    { return $this->belongsTo(Asset::class); }
    public function performer(): BelongsTo{ return $this->belongsTo(User::class, 'performed_by'); }
}