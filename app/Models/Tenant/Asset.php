<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'location_id',
        'asset_code', 'name', 'serial_number',
        'brand', 'model', 'description',
        'purchase_cost', 'purchase_date', 'warranty_until',
        'vendor', 'invoice_number',
        'condition', 'status',
        'qr_token', 'image',
        'specifications', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_cost'  => 'decimal:2',
            'purchase_date'  => 'date',
            'warranty_until' => 'date',
            'specifications' => 'array',
        ];
    }

    public function category(): BelongsTo  { return $this->belongsTo(AssetCategory::class, 'category_id'); }
    public function location(): BelongsTo  { return $this->belongsTo(Location::class); }
    public function assignments(): HasMany { return $this->hasMany(AssetAssignment::class); }
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('returned_at')->latestOfMany();
    }
    public function maintenance(): HasMany { return $this->hasMany(AssetMaintenance::class); }
}