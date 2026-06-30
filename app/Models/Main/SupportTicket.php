<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $connection = 'main';
    protected $table = 'support_tickets';

    protected $fillable = [
        'tenant_id', 'assigned_to',
        'ticket_number', 'subject', 'description',
        'priority', 'status', 'category',
        'attachments',
        'first_response_at', 'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments'       => 'array',
            'first_response_at' => 'datetime',
            'resolved_at'       => 'datetime',
            'closed_at'         => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id');
    }

    public static function generateNumber(): string
    {
        return 'TKT-'.strtoupper(uniqid());
    }
}