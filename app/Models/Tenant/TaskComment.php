<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends TenantModel
{
    use SoftDeletes;

    protected $table = 'task_comments';

    protected $fillable = [
        'task_id', 'user_id', 'parent_comment_id',
        'content', 'mentions', 'attachments', 'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'mentions'    => 'array',
            'attachments' => 'array',
            'is_edited'   => 'boolean',
        ];
    }

    public function task(): BelongsTo    { return $this->belongsTo(Task::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function parent(): BelongsTo  { return $this->belongsTo(self::class, 'parent_comment_id'); }
    public function replies(): HasMany   { return $this->hasMany(self::class, 'parent_comment_id'); }
}