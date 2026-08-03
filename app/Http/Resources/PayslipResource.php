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
            'payment_method' => $this->payment_method,
            'pay_schedule' => $this->whenLoaded('payrollRun', fn () => $this->payrollRun?->pay_schedule),
            'employee' => $this->whenLoaded('employee', fn () => [
                'full_name' => $this->employee?->full_name,
                'employee_code' => $this->employee?->employee_code,
                'position' => $this->employee?->relationLoaded('position') ? $this->employee?->position?->title : null,
                'department' => $this->employee?->relationLoaded('department') ? $this->employee?->department?->name : null,
            ]),
            'regular_hours' => (float) $this->regular_hours,
            'overtime_hours' => (float) $this->overtime_hours,
            'holiday_hours' => (float) $this->holiday_hours,
            'leave_hours' => (float) $this->leave_hours,
            'earnings' => collect([
                ['label' => 'Base Pay', 'amount' => (float) $this->base_pay],
                ['label' => 'Overtime Pay', 'amount' => (float) $this->overtime_pay],
                ['label' => 'Bonus', 'amount' => (float) $this->bonus],
                ['label' => 'Commission', 'amount' => (float) $this->commission],
                ['label' => 'Reimbursement', 'amount' => (float) $this->reimbursement],
            ])->filter(fn ($r) => $r['amount'] > 0)->values(),
            'deduction_breakdown' => collect([
                ['label' => 'Federal Income Tax', 'amount' => (float) $this->federal_tax],
                ['label' => 'State Tax', 'amount' => (float) $this->state_tax],
                ['label' => 'Local Tax', 'amount' => (float) $this->local_tax],
                ['label' => 'Social Security', 'amount' => (float) $this->fica_ss],
                ['label' => 'Medicare', 'amount' => (float) $this->fica_medicare],
                ['label' => 'Health Insurance', 'amount' => (float) $this->health_insurance],
                ['label' => '401(k) Retirement', 'amount' => (float) $this->retirement_401k],
                ['label' => 'Other Deductions', 'amount' => (float) $this->other_deductions],
            ])->filter(fn ($r) => $r['amount'] > 0)->values(),
            'gross_pay' => (float) $this->gross_pay,
            'net_pay' => (float) $this->net_pay,
            'total_deductions' => (float) $this->total_deductions,
            'tax_amount' => $this->tax_amount,
            'ytd_gross' => (float) $this->ytd_gross,
            'ytd_net' => (float) $this->ytd_net,
            'ytd_taxes' => (float) $this->ytd_taxes,
            'items' => PayslipItemResource::collection($this->whenLoaded('items')),
            'prev' => $this->when(isset($this->prev), fn () => $this->prev),
            'next' => $this->when(isset($this->next), fn () => $this->next),
        ];
    }
}
