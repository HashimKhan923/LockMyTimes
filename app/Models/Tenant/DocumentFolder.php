<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentFolder extends TenantModel
{
    protected $table = 'document_folders';

    protected $fillable = [
        'parent_id', 'name', 'color', 'icon',
        'visibility', 'allowed_roles', 'created_by',
    ];

    protected function casts(): array
    {
        return ['allowed_roles' => 'array'];
    }

    public function parent(): BelongsTo   { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany   { return $this->hasMany(self::class, 'parent_id'); }
    public function documents(): HasMany  { return $this->hasMany(Document::class, 'folder_id'); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
}