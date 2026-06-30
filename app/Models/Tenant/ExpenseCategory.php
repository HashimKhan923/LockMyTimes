<?php

namespace App\Models\Tenant;

class ExpenseCategory extends TenantModel
{
    protected $fillable = [
        'name', 'code', 'description',
        'requires_receipt', 'max_amount',
        'color', 'gl_code', 'is_active',
    ];

    protected $casts = [
        'requires_receipt' => 'boolean',
        'is_active'        => 'boolean',
        'max_amount'       => 'float',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}