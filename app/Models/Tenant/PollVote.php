<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends TenantModel
{
    protected $table = 'poll_votes';

    protected $fillable = [
        'poll_id', 'user_id', 'selected_options',
    ];

    protected function casts(): array
    {
        return ['selected_options' => 'array'];
    }

    public function poll(): BelongsTo { return $this->belongsTo(Poll::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}