<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportTicketReply extends Model
{
    use HasFactory;

    protected $connection = 'main';
    protected $table = 'support_ticket_replies';

    protected $fillable = [
        'ticket_id', 'author_type', 'author_id',
        'message', 'attachments', 'is_internal_note',
    ];

    protected function casts(): array
    {
        return [
            'attachments'      => 'array',
            'is_internal_note' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}