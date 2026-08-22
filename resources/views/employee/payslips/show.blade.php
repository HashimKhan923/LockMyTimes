@extends('layouts.employee')

@section('title', 'Payslip ' . $payslip->payslip_number)
@section('page-title', 'Payslip')

@push('head')
<style>
    @media print {
        .emp-sidebar, header, .ps-nav-bar { display:none !important; }
        .emp-content { padding:0 !important; }
        #payslip-card { box-shadow:none !important; border:none !important; }
        body { background:#fff !important; }
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto">

    @php
        $cur = $tenantCurrency ?? 'USD';
        $sym = match($cur) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY', 'CNY' => '¥',
            'INR' => '₹',
            'PKR' => 'Rs',
            'AED' => 'د.إ',
            'SAR' => '﷼',
            default => $cur.' ',
        };

        $earnings = collect([
            ['label' => 'Base Pay',      'amount' => (float) $payslip->base_pay],
            ['label' => 'Overtime Pay',  'amount' => (float) $payslip->overtime_pay],
            ['label' => 'Bonus',         'amount' => (float) $payslip->bonus],
            ['label' => 'Commission',    'amount' => (float) $payslip->commission],
            ['label' => 'Reimbursement', 'amount' => (float) $payslip->reimbursement],
        ])->filter(fn ($r) => $r['amount'] > 0);

        $deductions = collect([
            ['label' => 'Federal Income Tax', 'amount' => (float) $payslip->federal_tax],
            ['label' => 'State Tax',          'amount' => (float) $payslip->state_tax],
            ['label' => 'Local Tax',          'amount' => (float) $payslip->local_tax],
            ['label' => 'Social Security',    'amount' => (float) $payslip->fica_ss],
            ['label' => 'Medicare',           'amount' => (float) $payslip->fica_medicare],
            ['label' => 'Health Insurance',   'amount' => (float) $payslip->health_insurance],
            ['label' => '401(k) Retirement',  'amount' => (float) $payslip->retirement_401k],
            ['label' => 'Other Deductions',   'amount' => (float) $payslip->other_deductions],
        ])->filter(fn ($r) => $r['amount'] > 0);

        // Extra line items (component-based) that aren't already covered by the columns above
        $extraEarnings   = $payslip->items->where('type', 'earning');
        $extraReimburse  = $payslip->items->where('type', 'reimbursement');
        $extraDeductions = $payslip->items->where('type', 'deduction');
        $extraTaxes      = $payslip->items->where('type', 'tax');
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         TOP NAV BAR
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between mb-5 ps-nav-bar">
        <a href="{{ route('employee.payslips.index', $tenantSlug) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 dark:hover:text-slate-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>All Payslips</span>
        </a>

        <div class="flex items-center gap-1.5">
            @if($prev)
                <a href="{{ route('employee.payslips.show', [$tenantSlug, $prev->id]) }}"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors"
                   title="Previous payslip ({{ $prev->payslip_number }})">
                    <i data-lucide="chevron-left" class="w-4 h-4 text-gray-800"></i>
                </a>
            @endif
            @if($next)
                <a href="{{ route('employee.payslips.show', [$tenantSlug, $next->id]) }}"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors"
                   title="Next payslip ({{ $next->payslip_number }})">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-800"></i>
                </a>
            @endif
            <button type="button" onclick="window.print()"
                    class="lmt-btn-secondary lmt-btn-sm hidden sm:inline-flex">
                <i data-lucide="printer" class="w-4 h-4"></i> Print
            </button>
            <a href="{{ route('employee.payslips.pdf', [$tenantSlug, $payslip->id]) }}"
               class="lmt-btn-primary lmt-btn-sm">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Download PDF</span>
                <span class="sm:hidden">PDF</span>
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PAYSLIP CARD
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card" id="payslip-card" data-lmt-anim="fade-up">

        {{-- Header --}}
        <div class="rounded-2xl p-5 mb-6 text-white relative overflow-hidden"
             style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 70%,#7C3AED 100%);">
            <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <i data-lucide="clock" class="w-5 h-5 text-white/80"></i>
                        <span class="font-black text-lg">{{ $currentTenant->company_name ?? 'Lockmytimes' }}</span>
                    </div>
                    <p class="text-white/70 text-sm">Pay Slip</p>
                </div>
                <div class="text-right">
                    <p class="font-black text-xl">{{ $payslip->payslip_number }}</p>
                    <p class="text-white/70 text-sm">{{ $payslip->pay_date?->format('F j, Y') ?? '—' }}</p>
                    @php
                        $sc = [
                            'finalized' => 'bg-white/30',
                            'paid'      => 'bg-emerald-500',
                            'cancelled' => 'bg-red-500',
                        ];
                    @endphp
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold {{ $sc[$payslip->status] ?? 'bg-white/20' }}">
                        {{ $payslip->status === 'finalized' ? 'Issued' : ucfirst($payslip->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Employee + Period grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-100 dark:border-slate-700">
            <div>
                <p class="text-xs text-gray-800 mb-3 font-semibold uppercase tracking-wider">Employee</p>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-white"
                         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));">
                        {{ strtoupper(substr($payslip->employee->first_name ?? 'E', 0, 1)) }}{{ strtoupper(substr($payslip->employee->last_name ?? '', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-black text-gray-900 dark:text-slate-100 truncate">{{ $payslip->employee->full_name }}</p>
                        <p class="text-xs text-gray-800">{{ $payslip->employee->employee_code }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-600 dark:text-slate-400">{{ $payslip->employee->position?->title ?? '—' }}</p>
                <p class="text-xs text-gray-600 dark:text-slate-400">{{ $payslip->employee->department?->name ?? '—' }}</p>
            </div>

            <div>
                <p class="text-xs text-gray-800 mb-3 font-semibold uppercase tracking-wider">Pay Period</p>
                <div class="space-y-2 text-sm">
                    @foreach([
                        ['Period',    $payslip->period_start->format('M j') . ' – ' . $payslip->period_end->format('M j, Y')],
                        ['Pay Date',  $payslip->pay_date?->format('F j, Y') ?? '—'],
                        ['Schedule',  ucfirst(str_replace('_', ' ', $payslip->payrollRun->pay_schedule ?? 'monthly'))],
                        ['Payment',   ucfirst(str_replace('_', ' ', $payslip->payment_method))],
                    ] as [$k, $v])
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-800">{{ $k }}</span>
                            <span class="font-semibold text-gray-900 dark:text-slate-100 text-right">{{ $v }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═════════ EARNINGS ═════════ --}}
        <div class="mb-5">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-500"></i> Earnings
            </h4>
            <div class="space-y-1.5">
                @forelse($earnings as $row)
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm text-gray-700 dark:text-slate-300">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 font-mono">
                            {{ $sym }}{{ number_format($row['amount'], 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-gray-800 italic">No earnings on this payslip.</p>
                @endforelse

                @foreach($extraEarnings as $item)
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm text-gray-700 dark:text-slate-300">{{ $item->label }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 font-mono">
                            {{ $sym }}{{ number_format($item->amount, 2) }}
                        </span>
                    </div>
                @endforeach
                @foreach($extraReimburse as $item)
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm text-gray-700 dark:text-slate-300">{{ $item->label }} <span class="text-[10px] text-gray-800 font-bold uppercase">Reimbursement</span></span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 font-mono">
                            {{ $sym }}{{ number_format($item->amount, 2) }}
                        </span>
                    </div>
                @endforeach

                <div class="flex items-center justify-between py-2 border-t border-gray-100 dark:border-slate-700">
                    <span class="text-sm font-bold text-gray-900 dark:text-slate-100">Gross Pay</span>
                    <span class="font-black text-gray-900 dark:text-slate-100 font-mono">
                        {{ $sym }}{{ number_format($payslip->gross_pay, 2) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ═════════ DEDUCTIONS ═════════ --}}
        <div class="mb-5">
            <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                <i data-lucide="trending-down" class="w-3.5 h-3.5 text-red-500"></i> Deductions
            </h4>
            <div class="space-y-1.5">
                @forelse($deductions as $row)
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm text-gray-700 dark:text-slate-300">{{ $row['label'] }}</span>
                        <span class="text-sm font-semibold text-red-500 font-mono">
                            −{{ $sym }}{{ number_format($row['amount'], 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-gray-800 italic">No deductions on this payslip.</p>
                @endforelse

                @foreach($extraTaxes as $item)
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm text-gray-700 dark:text-slate-300">{{ $item->label }} <span class="text-[10px] text-gray-800 font-bold uppercase">Tax</span></span>
                        <span class="text-sm font-semibold text-red-500 font-mono">−{{ $sym }}{{ number_format($item->amount, 2) }}</span>
                    </div>
                @endforeach
                @foreach($extraDeductions as $item)
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm text-gray-700 dark:text-slate-300">{{ $item->label }}</span>
                        <span class="text-sm font-semibold text-red-500 font-mono">−{{ $sym }}{{ number_format($item->amount, 2) }}</span>
                    </div>
                @endforeach

                <div class="flex items-center justify-between py-2 border-t border-gray-100 dark:border-slate-700">
                    <span class="text-sm font-bold text-gray-900 dark:text-slate-100">Total Deductions</span>
                    <span class="font-black text-red-500 font-mono">
                        −{{ $sym }}{{ number_format($payslip->total_deductions, 2) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ═════════ NET PAY ═════════ --}}
        <div class="rounded-2xl p-5 mt-4"
             style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-emerald-700">Net Pay</p>
                    <p class="text-xs text-emerald-600 mt-0.5">Take-home amount</p>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-emerald-700 font-mono">
                    {{ $sym }}{{ number_format($payslip->net_pay, 2) }}
                </p>
            </div>
        </div>

        {{-- ═════════ YTD ═════════ --}}
        <div class="mt-5 pt-5 border-t border-gray-100 dark:border-slate-700 grid grid-cols-3 gap-3 text-center">
            @foreach([
                ['YTD Gross', $sym . number_format($payslip->ytd_gross, 0), 'text-gray-900 dark:text-slate-100'],
                ['YTD Taxes', $sym . number_format($payslip->ytd_taxes, 0), 'text-red-500'],
                ['YTD Net',   $sym . number_format($payslip->ytd_net, 0),   'text-emerald-600'],
            ] as [$label, $value, $color])
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-base font-black font-mono {{ $color }}">{{ $value }}</p>
                    <p class="text-[10px] text-gray-800 mt-0.5 font-bold uppercase tracking-wider">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        {{-- ═════════ HOURS ═════════ --}}
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            @php
                $hoursRows = [
                    ['label' => 'Regular',  'value' => number_format($payslip->regular_hours, 1),  'class' => 'text-gray-900 dark:text-slate-100', 'style' => '',                         'icon' => 'clock'],
                    ['label' => 'Overtime', 'value' => number_format($payslip->overtime_hours, 1), 'class' => '',                                  'style' => 'color:var(--brand-600);',  'icon' => 'zap'],
                    ['label' => 'Holiday',  'value' => number_format($payslip->holiday_hours, 1),  'class' => 'text-amber-600',                    'style' => '',                         'icon' => 'sun'],
                    ['label' => 'Leave',    'value' => number_format($payslip->leave_hours, 1),    'class' => 'text-purple-600',                   'style' => '',                         'icon' => 'calendar-off'],
                ];
            @endphp
            @foreach($hoursRows as $h)
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <i data-lucide="{{ $h['icon'] }}" class="w-3 h-3 text-gray-800"></i>
                        <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">{{ $h['label'] }} hrs</p>
                    </div>
                    <p class="text-base font-black font-mono {{ $h['class'] }}" @if($h['style']) style="{{ $h['style'] }}" @endif>
                        {{ $h['value'] }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- ═════════ FOOTER ═════════ --}}
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-700 text-center">
            <p class="text-xs text-gray-800">Computer-generated payslip · No signature required</p>
            <p class="text-[10px] text-gray-800 mt-1">
                Generated by {{ $currentTenant->company_name ?? 'Lockmytimes' }} · {{ now()->format('M j, Y h:i A') }}
            </p>
        </div>
    </div>

    {{-- Bottom spacing for mobile --}}
    <div class="h-6"></div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection