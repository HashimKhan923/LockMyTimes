@extends('layouts.employee')

@section('title', 'My Payslips')
@section('page-title', 'My Payslips')

@push('head')
<style>
    .ps-spark { display:flex; align-items:flex-end; gap:3px; height:48px; }
    .ps-spark > div {
        flex:1; min-width:6px;
        background:linear-gradient(180deg,rgba(255,255,255,.85),rgba(255,255,255,.35));
        border-radius:3px 3px 1px 1px;
        transition:height .3s;
    }
    .ps-spark > div.zero { background:rgba(255,255,255,.12); }
</style>
@endpush

@section('content')
<div>

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

        $maxNet = max(1, $monthly->max('net'));
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         HERO — Latest payslip + YTD summary
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 60%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/4 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 grid lg:grid-cols-[1.4fr_1fr] gap-6 items-stretch">

            {{-- Left: latest payslip --}}
            <div class="flex flex-col justify-between">
                <div>
                    <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Earnings</p>
                    <h1 class="text-white text-2xl lg:text-3xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                        {{ $year }} payslips
                    </h1>
                    <p class="text-white/75 text-sm mt-1.5">
                        {{ (int) $ytd->count }} payslip{{ $ytd->count == 1 ? '' : 's' }} this year &middot;
                        {{ $sym }}{{ number_format($ytd->net, 0) }} take-home
                    </p>
                </div>

                @if($latest)
                    <div class="mt-5 bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Most recent</p>
                                <p class="text-white font-black text-base mt-0.5 truncate">{{ $latest->payslip_number }}</p>
                                <p class="text-white/70 text-xs mt-0.5">
                                    {{ $latest->period_start->format('M j') }} – {{ $latest->period_end->format('M j, Y') }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-white text-2xl font-black font-mono">
                                    {{ $sym }}{{ number_format($latest->net_pay, 2) }}
                                </p>
                                <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider mt-0.5">Net pay</p>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('employee.payslips.show', [$tenantSlug, $latest->id]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white text-gray-900 text-xs font-bold hover:bg-white/95 transition-all">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                            </a>
                            <a href="{{ route('employee.payslips.pdf', [$tenantSlug, $latest->id]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 border border-white/25 text-white text-xs font-bold hover:bg-white/25 transition-all">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i> PDF
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: YTD card + year picker + sparkline --}}
            <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-5 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Year-to-date</p>
                    <form method="GET" action="{{ route('employee.payslips.index', $tenantSlug) }}">
                        <select name="year" onchange="this.form.submit()"
                                class="bg-white/15 border border-white/25 rounded-lg px-2.5 py-1 text-white font-bold text-xs appearance-none cursor-pointer pr-7"
                                style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 16 16&quot; fill=&quot;white&quot;><path d=&quot;M4 6l4 4 4-4&quot;/></svg>'); background-repeat:no-repeat; background-position:right 8px center;">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ (int) $y === (int) $year ? 'selected' : '' }} style="color:#1f2937;">{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Gross</p>
                        <p class="text-white text-xl font-black font-mono mt-0.5">
                            {{ $sym }}{{ number_format($ytd->gross, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Net</p>
                        <p class="text-white text-xl font-black font-mono mt-0.5">
                            {{ $sym }}{{ number_format($ytd->net, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Taxes</p>
                        <p class="text-white/90 text-sm font-bold font-mono mt-0.5">
                            {{ $sym }}{{ number_format($ytd->taxes, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Deductions</p>
                        <p class="text-white/90 text-sm font-bold font-mono mt-0.5">
                            {{ $sym }}{{ number_format($ytd->deductions, 0) }}
                        </p>
                    </div>
                </div>

                {{-- Monthly net sparkline --}}
                <div class="mt-4">
                    <div class="ps-spark" title="Monthly net pay">
                        @foreach($monthly as $m)
                            @php $h = $m['net'] > 0 ? max(8, round(($m['net'] / $maxNet) * 100)) : 0; @endphp
                            <div class="{{ $h === 0 ? 'zero' : '' }}" style="height: {{ $h === 0 ? 8 : $h }}%;" title="{{ $m['m'] }}: {{ $sym }}{{ number_format($m['net'], 0) }}"></div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-[9px] text-white/55 font-bold mt-1.5 uppercase tracking-wider">
                        <span>{{ $monthly->first()['m'] ?? '' }}</span>
                        <span>{{ $monthly->last()['m'] ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         LIST + FILTERS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">

        {{-- Header / filter bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-5 border-b border-gray-100 dark:border-slate-700">
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">All Payslips</h2>
                <p class="text-xs text-gray-800 mt-0.5">
                    {{ (int) $counters->total }} payslip{{ ($counters->total ?? 0) == 1 ? '' : 's' }} in {{ $year }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @php
                    $chips = [
                        ['key' => null,        'label' => 'All',       'count' => (int) ($counters->total ?? 0)],
                        ['key' => 'paid',      'label' => 'Paid',      'count' => (int) ($counters->p ?? 0)],
                        ['key' => 'finalized', 'label' => 'Issued',    'count' => (int) ($counters->f ?? 0)],
                        ['key' => 'cancelled', 'label' => 'Cancelled', 'count' => (int) ($counters->c ?? 0)],
                    ];
                @endphp
                @foreach($chips as $chip)
                    @php $isActive = $status === $chip['key']; @endphp
                    <a href="{{ route('employee.payslips.index', array_filter([
                            'tenant' => $tenantSlug,
                            'year' => $year,
                            'status' => $chip['key'],
                       ])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all
                              {{ $isActive
                                    ? 'border-transparent text-white'
                                    : 'border-gray-200 dark:border-slate-700 text-gray-800 hover:border-gray-300' }}"
                       @if($isActive) style="background:var(--brand-500);" @endif>
                        {{ $chip['label'] }}
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $isActive ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">
                            {{ $chip['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Table --}}
        @if($payslips->isEmpty())
            <div class="text-center py-16 px-5">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                    <i data-lucide="receipt" class="w-7 h-7 text-gray-800"></i>
                </div>
                <p class="text-sm font-bold text-gray-700 dark:text-slate-200">No payslips yet</p>
                <p class="text-xs text-gray-800 mt-1">
                    Your payslips will appear here once payroll is processed for {{ $year }}.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="lmt-table">
                    <thead>
                        <tr>
                            <th class="text-left">Payslip #</th>
                            <th class="text-left">Period</th>
                            <th class="text-left hidden md:table-cell">Pay Date</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Net</th>
                            <th class="text-left">Status</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payslips as $p)
                            @php
                                [$statusLbl, $statusCls, $statusStyle] = match($p->status) {
                                    'paid'      => ['Paid',      'lmt-badge-green', ''],
                                    'finalized' => ['Issued',    'lmt-badge-amber', ''],
                                    'cancelled' => ['Cancelled', 'lmt-badge-red',   ''],
                                    'draft'     => ['Draft',     'lmt-badge-gray',  ''],
                                    default     => [ucfirst($p->status), 'lmt-badge-gray', ''],
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50">
                                <td>
                                    <a href="{{ route('employee.payslips.show', [$tenantSlug, $p->id]) }}"
                                       class="font-mono text-xs font-bold hover:underline" style="color:var(--brand-600);">
                                        {{ $p->payslip_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900 dark:text-slate-100">
                                        {{ $p->period_start->format('M j') }}
                                        <span class="text-gray-800"></span>
                                        {{ $p->period_end->format('M j, Y') }}
                                    </div>
                                    @if($p->payrollRun?->pay_schedule)
                                        <p class="text-[10px] text-gray-800 font-semibold uppercase tracking-wide mt-0.5">
                                            {{ str_replace('_', ' ', $p->payrollRun->pay_schedule) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="hidden md:table-cell text-xs text-gray-800">
                                    {{ $p->pay_date?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    <span class="font-mono font-bold text-sm text-gray-700 dark:text-slate-200">
                                        {{ $sym }}{{ number_format($p->gross_pay, 2) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono font-black text-sm text-emerald-600">
                                        {{ $sym }}{{ number_format($p->net_pay, 2) }}
                                    </span>
                                </td>
                                <td><span class="{{ $statusCls }}">{{ $statusLbl }}</span></td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('employee.payslips.show', [$tenantSlug, $p->id]) }}"
                                           class="text-xs font-bold transition-colors hover:underline px-2 py-1"
                                           style="color:var(--brand-500);">
                                            View
                                        </a>
                                        <a href="{{ route('employee.payslips.pdf', [$tenantSlug, $p->id]) }}"
                                           title="Download PDF"
                                           class="inline-flex items-center justify-center w-7 h-7 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-800 hover:text-gray-700 transition-colors">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                {{ $payslips->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection