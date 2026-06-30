<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'folder_id', 'uploaded_by',
        'title', 'description',
        'file_path', 'file_name', 'mime_type', 'file_size',
        'version', 'parent_document_id',
        'category', 'visibility',
        'allowed_roles', 'allowed_employees',
        'expiry_date',
        'requires_acknowledgment', 'requires_signature',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'allowed_roles'           => 'array',
            'allowed_employees'       => 'array',
            'expiry_date'             => 'date',
            'requires_acknowledgment' => 'boolean',
            'requires_signature'      => 'boolean',
        ];
    }

    public function folder(): BelongsTo     { return $this->belongsTo(DocumentFolder::class, 'folder_id'); }
    public function uploader(): BelongsTo   { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function parent(): BelongsTo     { return $this->belongsTo(self::class, 'parent_document_id'); }
    public function versions(): HasMany     { return $this->hasMany(self::class, 'parent_document_id'); }
    public function signatures(): HasMany   { return $this->hasMany(DocumentSignature::class); }
}