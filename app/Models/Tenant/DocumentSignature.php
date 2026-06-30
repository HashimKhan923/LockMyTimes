<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignature extends TenantModel
{
    protected $table = 'document_signatures';

    protected $fillable = [
        'document_id', 'employee_id',
        'action', 'signature_data', 'signed_at',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}