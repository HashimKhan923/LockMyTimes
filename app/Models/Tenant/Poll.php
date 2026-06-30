<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends TenantModel
{
    protected $fillable = [
        'created_by', 'question', 'description',
        'type', 'options', 'is_anonymous',
        'starts_at', 'ends_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'options'      => 'array',
            'is_anonymous' => 'boolean',
            'starts_at'    => 'datetime',
            'ends_at'      => 'datetime',
        ];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function votes(): HasMany     { return $this->hasMany(PollVote::class); }

    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    public function getResultsAttribute(): array
    {
        $options    = $this->options ?? [];
        $totalVotes = $this->total_votes;

        return collect($options)->map(function ($option) use ($totalVotes) {
            $optionLabel = is_array($option) ? ($option['text'] ?? $option['label'] ?? (string)$option) : (string)$option;

            $count = $this->votes()
                ->whereJsonContains('selected_options', $optionLabel)
                ->count();

            return [
                'option'  => $optionLabel,
                'votes'   => $count,
                'percent' => $totalVotes > 0 ? round($count / $totalVotes * 100) : 0,
            ];
        })->toArray();
    }
}