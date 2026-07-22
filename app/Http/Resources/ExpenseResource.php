<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_number' => $this->expense_number,
            'category' => new ExpenseCategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'expense_date' => $this->expense_date?->toDateString(),
            'merchant' => $this->merchant,
            'payment_method' => $this->payment_method,
            'project_code' => $this->project_code,
            'receipt_url' => $this->receipt_url,
            'is_mileage' => (bool) $this->is_mileage,
            'miles' => $this->miles,
            'mileage_rate' => $this->mileage_rate,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'timeline' => $this->when(isset($this->timeline), fn () => $this->timeline),
        ];
    }
}
