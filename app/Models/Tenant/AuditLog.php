<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends TenantModel
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'event',
        'auditable_type', 'auditable_id',
        'old_values', 'new_values',
        'ip_address', 'user_agent', 'url',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quick static helper to record an audit event.
     */
    public static function record(
        string $event,
        ?object $model = null,
        array $oldValues = [],
        array $newValues = []
    ): self {
        return static::create([
            'user_id'         => auth()->id(),
            'event'           => $event,
            'auditable_type'  => $model ? get_class($model) : null,
            'auditable_id'    => $model?->getKey(),
            'old_values'      => $oldValues,
            'new_values'      => $newValues,
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
            'url'             => request()->fullUrl(),
        ]);
    }
}