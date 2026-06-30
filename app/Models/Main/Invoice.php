<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $connection = 'main';
    protected $table = 'invoices';

    protected $fillable = [
        'tenant_id', 'subscription_id', 'payment_id',
        'invoice_number', 'stripe_invoice_id',
        'subtotal', 'tax_amount', 'discount_amount', 'total',
        'amount_paid', 'amount_due', 'currency',
        'status',
        'issued_date', 'due_date', 'paid_at',
        'line_items', 'billing_address', 'notes',
        'pdf_url', 'hosted_invoice_url',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total'           => 'decimal:2',
            'amount_paid'     => 'decimal:2',
            'amount_due'      => 'decimal:2',
            'issued_date'     => 'date',
            'due_date'        => 'date',
            'paid_at'         => 'datetime',
            'line_items'      => 'array',
            'billing_address' => 'array',
        ];
    }

    public function tenant(): BelongsTo         { return $this->belongsTo(Tenant::class); }
    public function subscription(): BelongsTo   { return $this->belongsTo(Subscription::class); }
    public function payment(): BelongsTo        { return $this->belongsTo(Payment::class); }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Generate a unique invoice number like LMT-2025-000001.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('invoice_number', 'like', "LMT-{$year}-%")->latest('id')->first();
        $next = $last
            ? ((int) substr($last->invoice_number, -6)) + 1
            : 1;
        return 'LMT-'.$year.'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}