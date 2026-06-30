<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\Cache;

class Setting extends TenantModel
{
    protected $fillable = [
        'key', 'value', 'group', 'type', 'is_public',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    /**
     * Return the value cast to its declared type — used in Blade via $setting->casted_value.
     */
    public function getCastedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    /**
     * Get a setting value by key with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) return $default;

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    /**
     * Set a setting value by key (upsert).
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
    }

    /**
     * Upsert a setting with group and type metadata.
     */
    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type'  => $type,
            ]
        );
    }

    /**
     * Get all settings for a given group as key => value array.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->get()
            ->mapWithKeys(fn ($s) => [$s->key => $s->value])
            ->toArray();
    }
}