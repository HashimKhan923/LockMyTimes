<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $connection = 'main';
    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id', 'plan_id',
        'stripe_subscription_id', 'stripe_price_id',
        'billing_cycle', 'amount', 'currency',
        'status',
        'trial_starts_at', 'trial_ends_at',
        'current_period_starts_at', 'current_period_ends_at',
        'cancelled_at', 'ends_at', 'cancel_at_period_end',
    ];

    protected function casts(): array
    {
        return [
            'amount'                   => 'decimal:2',
            'cancel_at_period_end'     => 'boolean',
            'trial_starts_at'          => 'datetime',
            'trial_ends_at'            => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at'   => 'datetime',
            'cancelled_at'             => 'datetime',
            'ends_at'                  => 'datetime',
        ];
    }

    /* =====================================================
     | Relationships
     |====================================================*/

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /* =====================================================
     | Helpers
     |====================================================*/

    public function isActive(): bool
    {
        return in_array($this->status, ['trialing', 'active']);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || ! is_null($this->cancelled_at);
    }

    public function daysUntilRenewal(): ?int
    {
        return $this->current_period_ends_at?->diffInDays(now()) ?? null;
    }
}