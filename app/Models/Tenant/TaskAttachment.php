<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends TenantModel
{
    protected $table = 'task_attachments';

    protected $fillable = [
        'task_id', 'uploaded_by',
        'file_path', 'file_name',
        'mime_type', 'file_size', 'thumbnail',
    ];

    public function task(): BelongsTo     { return $this->belongsTo(Task::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return $bytes.' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1).' KB';
        return round($bytes / 1048576, 1).' MB';
    }
}