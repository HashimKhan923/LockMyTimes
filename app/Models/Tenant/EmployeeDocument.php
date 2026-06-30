<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends TenantModel
{
    protected $table = 'employee_documents';

    protected $fillable = [
        'employee_id', 'uploaded_by',
        'document_type', 'title',
        'file_path', 'file_name', 'mime_type', 'file_size',
        'issue_date', 'expiry_date',
        'notes', 'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'      => 'date',
            'expiry_date'     => 'date',
            'is_confidential' => 'boolean',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}