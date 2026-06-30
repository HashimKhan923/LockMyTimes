<?php

namespace App\Models\Tenant;

class CustomField extends TenantModel
{
    protected $table = 'custom_fields';

    protected $fillable = [
        'module', 'label', 'field_key',
        'type', 'options',
        'is_required', 'is_visible', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options'     => 'array',
            'is_required' => 'boolean',
            'is_visible'  => 'boolean',
        ];
    }

    /**
     * Get all custom fields for a given module, sorted.
     */
    public static function forModule(string $module): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('module', $module)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();
    }
}