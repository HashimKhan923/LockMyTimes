<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanRepaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'installment_number' => $this->installment_number,
            'due_date' => $this->due_date?->toDateString(),
            'emi_amount' => (float) $this->emi_amount,
            'principal_component' => (float) $this->principal_component,
            'interest_component' => (float) $this->interest_component,
            'balance_after' => (float) $this->balance_after,
            'paid_date' => $this->paid_date?->toDateString(),
            'amount_paid' => (float) $this->amount_paid,
            'status' => $this->status,
            'is_overdue' => $this->status === 'pending' && $this->due_date?->isPast(),
        ];
    }
}
