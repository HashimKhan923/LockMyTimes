<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends TenantModel
{
    protected $table = 'asset_categories';

    protected $fillable = ['name', 'icon', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}