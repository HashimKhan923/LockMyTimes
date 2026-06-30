<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $connection = 'main';
    protected $table = 'audit_logs';

    protected $fillable = [
        'super_admin_id', 'tenant_id',
        'event', 'auditable_type', 'auditable_id',
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

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Quick helper to log an event.
     */
    public static function record(string $event, array $data = []): self
    {
        return static::create(array_merge([
            'event'      => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url'        => request()->fullUrl(),
        ], $data));
    }
}