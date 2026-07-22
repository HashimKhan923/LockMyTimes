<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

class ExpenseCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color ?? '#6C7DF7',
            'requires_receipt' => (bool) $this->requires_receipt,
            'max_amount' => $this->max_amount,
            'per_expense_limit' => Schema::connection('tenant')->hasColumn('expense_categories', 'per_expense_limit')
                ? $this->per_expense_limit
                : null,
            'receipt_required_above' => Schema::connection('tenant')->hasColumn('expense_categories', 'receipt_required_above')
                ? $this->receipt_required_above
                : null,
        ];
    }
}
