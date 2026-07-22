<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color ?? '#6C7DF7',
            'default_interest_rate' => (float) $this->default_interest_rate,
            'max_amount' => $this->max_amount,
            'min_amount' => $this->min_amount,
            'max_tenure_months' => $this->max_tenure_months,
            'min_tenure_months' => $this->min_tenure_months,
            'min_service_months' => $this->min_service_months,
            'min_salary' => $this->min_salary,
            'max_salary_multiplier' => $this->max_salary_multiplier,
            'requires_guarantor' => (bool) $this->requires_guarantor,
            // Eligibility fields — populated by the controller (see
            // LoanController::createLoan) for the same-shaped web page.
            'is_eligible' => $this->when(isset($this->_eligible), fn () => $this->_eligible),
            'block_reasons' => $this->when(isset($this->_block_reasons), fn () => $this->_block_reasons),
            'effective_max' => $this->when(isset($this->_effective_max), fn () => $this->_effective_max),
        ];
    }
}
