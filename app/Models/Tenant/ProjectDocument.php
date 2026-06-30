<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends TenantModel
{
    protected $table = 'project_documents';

    protected $fillable = [
        'project_id', 'uploaded_by',
        'title', 'description',
        'file_path', 'file_name', 'mime_type', 'file_size',
    ];

    public function project(): BelongsTo  { return $this->belongsTo(Project::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}