<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payslip_number' => $this->payslip_number,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'pay_date' => $this->pay_date?->toDateString(),
            'status' => $this->status,
            'regular_hours' => (float) $this->regular_hours,
            'overtime_hours' => (float) $this->overtime_hours,
            'gross_pay' => (float) $this->gross_pay,
            'net_pay' => (float) $this->net_pay,
            'total_deductions' => (float) $this->total_deductions,
            'tax_amount' => $this->tax_amount,
            'items' => PayslipItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
