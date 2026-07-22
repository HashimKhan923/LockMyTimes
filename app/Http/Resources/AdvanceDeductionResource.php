<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvanceDeductionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deduction_number' => $this->deduction_number,
            'deduction_date' => $this->deduction_date?->toDateString(),
            'amount' => (float) $this->amount,
            'status' => $this->status,
        ];
    }
}
