<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_number' => $this->loan_number,
            'loan_type' => new LoanTypeResource($this->whenLoaded('loanType')),
            'principal_amount' => (float) $this->principal_amount,
            'interest_rate' => (float) $this->interest_rate,
            'total_interest' => (float) $this->total_interest,
            'total_amount' => (float) $this->total_amount,
            'tenure_months' => $this->tenure_months,
            'emi_amount' => (float) $this->emi_amount,
            'first_emi_date' => $this->first_emi_date?->toDateString(),
            'amount_paid' => (float) $this->amount_paid,
            'amount_remaining' => (float) $this->amount_remaining,
            'installments_paid' => $this->installments_paid,
            'installments_remaining' => $this->installments_remaining,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'approver_comments' => $this->approver_comments,
            'rejection_reason' => $this->rejection_reason,
            'disbursement_method' => $this->disbursement_method,
            'disbursement_reference' => $this->disbursement_reference,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'disbursed_at' => $this->disbursed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'progress_pct' => $this->total_amount > 0
                ? round(((float) $this->amount_paid / (float) $this->total_amount) * 100, 1)
                : 0,
            'repayments' => LoanRepaymentResource::collection($this->whenLoaded('repayments')),
            'timeline' => $this->when(isset($this->timeline), fn () => $this->timeline),
        ];
    }
}
