@extends('layouts.admin')
@section('title','Payslip — '.$payslip->payslip_number)
@section('page-title','Payslip')

@section('content')

<div class="max-w-2xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('admin.payroll.show', [$tenant, $payslip->payroll_run_id]) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Run
        </a>
        <button onclick="window.print()" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="printer" class="w-4 h-4"></i>
            Print
        </button>
    </div>

    <div class="lmt-card" id="payslip-card">

        {{-- Header --}}
        <div class="lmt-gradient-bg rounded-xl p-5 mb-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/2"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <i data-lucide="clock" class="w-5 h-5 text-white/80"></i>
                        <span class="font-black text-lg">Lockmytimes</span>
                    </div>
                    <p class="text-white/70 text-sm">Pay Slip</p>
                </div>
                <div class="text-right">
                    <p class="font-black text-xl">{{ $payslip->payslip_number }}</p>
                    <p class="text-white/70 text-sm">{{ $payslip->pay_date?->format('F j, Y') }}</p>
                    @php $sc = ['draft'=>'bg-white/20','finalized'=>'bg-white/30','paid'=>'bg-emerald-500']; @endphp
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold {{ $sc[$payslip->status] ?? 'bg-white/20' }}">
                        {{ ucfirst($payslip->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Employee Info --}}
        <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-800 mb-3 font-semibold uppercase tracking-wider">Employee</p>
                <div class="flex items-center gap-3 mb-3">
                    <div class="lmt-avatar-md font-black">{{ substr($payslip->employee->first_name ?? 'E',0,1) }}</div>
                    <div>
                        <p class="font-black text-gray-900">{{ $payslip->employee->full_name }}</p>
                        <p class="text-xs text-gray-800">{{ $payslip->employee->employee_code }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-800">{{ $payslip->employee->position?->title ?? 'N/A' }}</p>
                <p class="text-xs text-gray-800">{{ $payslip->employee->department?->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-800 mb-3 font-semibold uppercase tracking-wider">Pay Period</p>
                <div class="space-y-2 text-sm">
                    @foreach([
                        ['Period',    $payslip->period_start->format('M j').' – '.$payslip->period_end->format('M j, Y')],
                        ['Pay Date',  $payslip->pay_date?->format('F j, Y') ?? '—'],
                        ['Schedule',  ucfirst(str_replace('_',' ', $payslip->payrollRun->pay_schedule ?? 'monthly'))],
                        ['Payment',   ucfirst(str_replace('_',' ', $payslip->payment_method))],
                    ] as [$k,$v])
                    <div class="flex justify-between">
                        <span class="text-gray-800">{{ $k }}</span>
                        <span class="font-semibold text-gray-900">{{ $v }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Earnings --}}
        <div class="mb-5">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">Earnings</h4>
            <div class="space-y-2">
                @foreach([
                    ['Base Pay',        $payslip->base_pay],
                    ['Overtime Pay',    $payslip->overtime_pay],
                    ['Bonus',           $payslip->bonus],
                    ['Commission',      $payslip->commission],
                    ['Reimbursement',   $payslip->reimbursement],
                ] as [$label, $amount])
                @if($amount > 0)
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-sm text-gray-800">{{ $label }}</span>
                    <span class="text-sm font-semibold text-gray-900">${{ number_format($amount, 2) }}</span>
                </div>
                @endif
                @endforeach
                <div class="flex items-center justify-between py-2 border-t border-gray-100">
                    <span class="text-sm font-bold text-gray-900">Gross Pay</span>
                    <span class="font-black text-gray-900">${{ number_format($payslip->gross_pay, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Deductions --}}
        <div class="mb-5">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">Deductions</h4>
            <div class="space-y-2">
                @foreach([
                    ['Federal Income Tax',  $payslip->federal_tax],
                    ['State Tax',           $payslip->state_tax],
                    ['Social Security',     $payslip->fica_ss],
                    ['Medicare',            $payslip->fica_medicare],
                    ['Health Insurance',    $payslip->health_insurance],
                    ['401(k)',              $payslip->retirement_401k],
                    ['Other Deductions',    $payslip->other_deductions],
                ] as [$label, $amount])
                @if($amount > 0)
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-sm text-gray-800">{{ $label }}</span>
                    <span class="text-sm font-semibold text-red-500">-${{ number_format($amount, 2) }}</span>
                </div>
                @endif
                @endforeach
                <div class="flex items-center justify-between py-2 border-t border-gray-100">
                    <span class="text-sm font-bold text-gray-900">Total Deductions</span>
                    <span class="font-black text-red-500">-${{ number_format($payslip->total_deductions, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Net Pay --}}
        <div class="rounded-2xl p-5 mt-4" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-emerald-700">Net Pay</p>
                    <p class="text-xs text-emerald-600 mt-0.5">Take-home amount</p>
                </div>
                <p class="text-3xl font-black text-emerald-700">${{ number_format($payslip->net_pay, 2) }}</p>
            </div>
        </div>

        {{-- YTD --}}
        <div class="mt-5 pt-5 border-t border-gray-100 grid grid-cols-3 gap-3 text-center">
            @foreach([
                ['YTD Gross', '$'.number_format($payslip->ytd_gross,0), 'text-gray-900'],
                ['YTD Taxes', '$'.number_format($payslip->ytd_taxes,0), 'text-red-500'],
                ['YTD Net',   '$'.number_format($payslip->ytd_net,0),   'text-emerald-600'],
            ] as [$label,$value,$color])
            <div class="bg-gray-50 rounded-xl p-3">
                <p class="text-base font-black {{ $color }}">{{ $value }}</p>
                <p class="text-xs text-gray-800 mt-0.5">{{ $label }}</p>
            </div>
            @endforeach
        </div>

        {{-- Hours summary --}}
        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-4 gap-3 text-center">
            @foreach([
                ['Regular Hrs',  number_format($payslip->regular_hours,1),  'text-gray-900'],
                ['Overtime Hrs', number_format($payslip->overtime_hours,1), 'text-brand-600'],
                ['Holiday Hrs',  number_format($payslip->holiday_hours,1),  'text-amber-600'],
                ['Leave Hrs',    number_format($payslip->leave_hours,1),    'text-purple-600'],
            ] as [$label,$value,$color])
            <div class="bg-gray-50 rounded-xl p-3">
                <p class="text-base font-black {{ $color }}">{{ $value }}</p>
                <p class="text-xs text-gray-800 mt-0.5">{{ $label }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-6 pt-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-800">Computer-generated payslip · No signature required</p>
            <p class="text-xs text-gray-800 mt-1">Generated by Lockmytimes · {{ now()->format('F j, Y h:i A') }}</p>
        </div>
    </div>
</div>

@endsection

@push('head')
<style>
@media print {
    .adm-sidebar, header, .adm-content > div:first-child { display:none !important; }
    .adm-content { padding:0 !important; }
    #payslip-card { box-shadow:none !important; }
    body { background:#fff !important; }
}
</style>
@endpush
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush