<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $connection = 'main';
    protected $table = 'payments';

    protected $fillable = [
        'tenant_id', 'subscription_id',
        'stripe_payment_intent_id', 'stripe_charge_id', 'stripe_invoice_id',
        'amount', 'amount_refunded', 'currency', 'tax_amount',
        'status', 'payment_method', 'card_brand', 'card_last4',
        'failure_code', 'failure_message', 'receipt_url', 'description',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'amount_refunded' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'paid_at'         => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}