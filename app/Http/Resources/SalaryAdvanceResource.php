<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryAdvanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'advance_number' => $this->advance_number,
            'created_at' => $this->created_at?->toIso8601String(),
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'repayment_type' => $this->repayment_type,
            'installments_count' => $this->installments_count,
            'per_installment_amount' => (float) $this->per_installment_amount,
            'first_deduction_date' => $this->first_deduction_date?->toDateString(),
            'amount_repaid' => (float) $this->amount_repaid,
            'amount_remaining' => (float) $this->amount_remaining,
            'installments_paid' => $this->installments_paid,
            'status' => $this->status,
            'approver_comments' => $this->approver_comments,
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'disbursed_at' => $this->disbursed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'progress_pct' => $this->amount > 0
                ? round(((float) $this->amount_repaid / (float) $this->amount) * 100, 1)
                : 0,
            'deductions' => AdvanceDeductionResource::collection($this->whenLoaded('deductions')),
            'timeline' => $this->when(isset($this->timeline), fn () => $this->timeline),
        ];
    }
}
