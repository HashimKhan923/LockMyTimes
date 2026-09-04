@extends('layouts.employee')

@section('title', 'Payslip ' . $payslip->payslip_number)
@section('page-title', 'Payslip')

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

        // Itemized breakdown — the single source of truth for what's shown below.
        // (Don't also render the aggregate base_pay/bonus/etc columns here: PayrollService
        // writes every one of those as its own item too, so showing both would double-count
        // every line on screen against the real gross_pay/total_deductions totals.)
        $itemsByType = $payslip->items->groupBy('type');
        $earnings    = $itemsByType->get('earning', collect());
        $deductions  = $itemsByType->get('tax', collect())->merge($itemsByType->get('deduction', collect()));
        $reimburse   = $itemsByType->get('reimbursement', collect());
        $sc = ['finalized' => 'bg-white/20 text-white', 'paid' => 'bg-emerald-500 text-white', 'cancelled' => 'bg-red-500 text-white'];
    @endphp

    {{-- ═══════════════════ TOP NAV BAR ═══════════════════ --}}
    <div class="flex items-center justify-between mb-5 ps-toolbar">
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

    {{-- ═══════════════════ PAYSLIP DOCUMENT ═══════════════════ --}}
    <div class="ps-doc" id="payslip-card" data-lmt-anim="fade-up">

        {{-- Letterhead --}}
        <div class="ps-letterhead">
            <div>
                @if(isset($currentTenant) && $currentTenant->logo)
                <img src="{{ $currentTenant->logo_url }}" class="ps-logo" alt="{{ $currentTenant->company_name }}"/>
                @else
                <p class="ps-company">{{ $currentTenant->company_name ?? 'Lockmytimes' }}</p>
                @endif
                <p class="ps-doctype">Pay Statement</p>
            </div>
            <div class="text-right">
                <p class="ps-number">{{ $payslip->payslip_number }}</p>
                <p class="ps-paydate">Pay date {{ $payslip->pay_date?->format('F j, Y') ?? '—' }}</p>
                <span class="ps-status {{ $sc[$payslip->status] ?? '' }}">{{ $payslip->status === 'finalized' ? 'Issued' : ucfirst($payslip->status) }}</span>
            </div>
        </div>
        <div class="ps-rule"></div>

        {{-- Employee + Period --}}
        <div class="ps-meta">
            <div>
                <p class="ps-meta-label">Paid To</p>
                <div class="flex items-center gap-3 mt-2">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-white flex-shrink-0"
                         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));">
                        {{ strtoupper(substr($payslip->employee->first_name ?? 'E', 0, 1)) }}{{ strtoupper(substr($payslip->employee->last_name ?? '', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-black text-gray-900 dark:text-slate-100 truncate">{{ $payslip->employee->full_name }}</p>
                        <p class="text-xs text-gray-800">{{ $payslip->employee->employee_code }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-800 dark:text-slate-400 mt-2">{{ $payslip->employee->position?->title ?? '—' }} &middot; {{ $payslip->employee->department?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="ps-meta-label mb-2">Pay Period</p>
                @foreach([
                    ['Period',    $payslip->period_start->format('M j') . ' – ' . $payslip->period_end->format('M j, Y')],
                    ['Pay Date',  $payslip->pay_date?->format('F j, Y') ?? '—'],
                    ['Schedule',  ucfirst(str_replace('_', ' ', $payslip->payrollRun->pay_schedule ?? 'monthly'))],
                    ['Payment',   ucfirst(str_replace('_', ' ', $payslip->payment_method))],
                ] as [$k, $v])
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-800">{{ $k }}</span>
                        <span class="font-semibold text-gray-900 dark:text-slate-100 text-right">{{ $v }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Earnings | Deductions side by side --}}
        <div class="ps-columns">
            <div class="ps-col">
                <p class="ps-col-head earn">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5"></i> Earnings
                </p>
                @forelse($earnings as $item)
                    <div class="ps-line">
                        <span>{{ $item->label }}</span>
                        <span class="ps-amt">{{ $sym }}{{ number_format($item->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="ps-empty">No earnings on this payslip.</p>
                @endforelse
                <div class="ps-line ps-subtotal">
                    <span>Gross Pay</span>
                    <span class="ps-amt">{{ $sym }}{{ number_format($payslip->gross_pay, 2) }}</span>
                </div>
            </div>
            <div class="ps-col">
                <p class="ps-col-head ded">
                    <i data-lucide="trending-down" class="w-3.5 h-3.5"></i> Deductions
                </p>
                @forelse($deductions as $item)
                    <div class="ps-line">
                        <span>{{ $item->label }}</span>
                        <span class="ps-amt neg">−{{ $sym }}{{ number_format($item->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="ps-empty">No deductions on this payslip.</p>
                @endforelse
                <div class="ps-line ps-subtotal">
                    <span>Total Deductions</span>
                    <span class="ps-amt neg">−{{ $sym }}{{ number_format($payslip->total_deductions, 2) }}</span>
                </div>
            </div>
        </div>

        @if($reimburse->isNotEmpty())
        <div class="ps-reimburse">
            <i data-lucide="badge-check" class="w-4 h-4 flex-shrink-0"></i>
            <div class="flex-1 min-w-0 space-y-1">
                @foreach($reimburse as $item)
                <div class="flex justify-between text-sm">
                    <span>{{ $item->label }} <span class="ps-tag">Non-taxable reimbursement</span></span>
                    <span class="font-bold">+{{ $sym }}{{ number_format($item->amount, 2) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Net Pay --}}
        <div class="ps-netpay">
            <div>
                <p class="ps-netpay-label">Net Pay</p>
                <p class="ps-netpay-sub">Take-home amount</p>
            </div>
            <p class="ps-netpay-amt">{{ $sym }}{{ number_format($payslip->net_pay, 2) }}</p>
        </div>

        {{-- Summary strip --}}
        <div class="ps-summary">
            @foreach([
                ['YTD Gross', $sym . number_format($payslip->ytd_gross, 0)],
                ['YTD Taxes', $sym . number_format($payslip->ytd_taxes, 0)],
                ['YTD Net',   $sym . number_format($payslip->ytd_net, 0)],
                ['Regular Hrs',  number_format($payslip->regular_hours, 1)],
                ['Overtime Hrs', number_format($payslip->overtime_hours, 1)],
                ['Leave Hrs',    number_format($payslip->leave_hours, 1)],
            ] as [$label, $value])
                <div class="ps-stat">
                    <p class="ps-stat-v">{{ $value }}</p>
                    <p class="ps-stat-l">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="ps-footer">
            <p>This is a computer-generated pay statement and requires no signature.</p>
            <p>Issued by {{ $currentTenant->company_name ?? 'Lockmytimes' }} on {{ now()->format('F j, Y') }}</p>
        </div>
    </div>

    {{-- Bottom spacing for mobile --}}
    <div class="h-6"></div>
</div>

@push('head')
<style>
    .ps-doc { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
    .ps-letterhead { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
    .ps-logo { height:44px; width:auto; max-width:200px; object-fit:contain; }
    .ps-company { font-size:1.25rem; font-weight:900; color:#111827; }
    .ps-doctype { font-size:.75rem; color:var(--brand-600); font-weight:700; text-transform:uppercase; letter-spacing:.08em; margin-top:4px; }
    .ps-number { font-size:1.05rem; font-weight:900; color:#111827; }
    .ps-paydate { font-size:.75rem; color:#6b7280; margin-top:2px; }
    .ps-status { display:inline-block; margin-top:8px; padding:3px 12px; border-radius:999px; font-size:.7rem; font-weight:800; background:var(--brand-50); color:var(--brand-700); }
    .ps-rule { height:3px; border-radius:2px; margin:20px 0 24px; background:linear-gradient(90deg,var(--brand-500),var(--brand-600) 60%,transparent); }
    .ps-meta { display:grid; grid-template-columns:1fr 1fr; gap:32px; padding-bottom:24px; margin-bottom:24px; border-bottom:1px dashed #e5e7eb; }
    .ps-meta-label { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; }
    .ps-columns { display:grid; grid-template-columns:1fr 1fr; gap:0; border:1px solid #f1f5f9; border-radius:12px; overflow:hidden; margin-bottom:20px; }
    .ps-col { padding:16px 20px; }
    .ps-col:first-child { border-right:1px solid #f1f5f9; }
    .ps-col-head { display:flex; align-items:center; gap:6px; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; padding-bottom:10px; margin-bottom:10px; border-bottom:2px solid; }
    .ps-col-head.earn { color:#059669; border-color:#d1fae5; }
    .ps-col-head.ded { color:#dc2626; border-color:#fee2e2; }
    .ps-line { display:flex; justify-content:space-between; gap:10px; font-size:.85rem; color:#374151; padding:5px 0; }
    .ps-amt { font-weight:600; color:#111827; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .ps-amt.neg { color:#dc2626; }
    .ps-subtotal { margin-top:6px; padding-top:10px; border-top:1px solid #f1f5f9; font-weight:800; color:#111827; }
    .ps-subtotal .ps-amt { font-weight:900; }
    .ps-empty { font-size:.8rem; color:#9ca3af; font-style:italic; padding:4px 0; }
    .ps-reimburse { display:flex; gap:10px; align-items:flex-start; background:#eff6ff; color:#1d4ed8; border-radius:10px; padding:12px 14px; margin-bottom:20px; }
    .ps-tag { font-size:.62rem; font-weight:700; text-transform:uppercase; color:#60a5fa; margin-left:4px; }
    .ps-netpay { display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#0f766e,#059669); border-radius:14px; padding:20px 24px; margin-bottom:24px; }
    .ps-netpay-label { color:rgba(255,255,255,.85); font-size:.8rem; font-weight:700; }
    .ps-netpay-sub { color:rgba(255,255,255,.6); font-size:.7rem; margin-top:2px; }
    .ps-netpay-amt { color:#fff; font-size:2rem; font-weight:900; font-variant-numeric:tabular-nums; }
    .ps-summary { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:20px; }
    .ps-stat { background:#f9fafb; border-radius:10px; padding:10px 6px; text-align:center; }
    .ps-stat-v { font-size:.85rem; font-weight:800; color:#111827; font-variant-numeric:tabular-nums; }
    .ps-stat-l { font-size:.62rem; color:#9ca3af; margin-top:2px; }
    .ps-footer { text-align:center; padding-top:20px; border-top:1px solid #f1f5f9; font-size:.7rem; color:#9ca3af; line-height:1.6; }

    .dark .ps-doc { background:#1e293b; border-color:#334155; }
    .dark .ps-company, .dark .ps-number { color:#f1f5f9; }
    .dark .ps-status { background:rgba(108,125,247,.15); }
    .dark .ps-meta { border-color:#334155; }
    .dark .ps-columns { border-color:#334155; }
    .dark .ps-col:first-child { border-color:#334155; }
    .dark .ps-line { color:#cbd5e1; }
    .dark .ps-amt { color:#f1f5f9; }
    .dark .ps-subtotal { border-color:#334155; color:#f1f5f9; }
    .dark .ps-stat { background:#0f172a; }
    .dark .ps-stat-v { color:#f1f5f9; }
    .dark .ps-footer { border-color:#334155; }

    @media (max-width: 640px) {
        .ps-meta, .ps-columns { grid-template-columns:1fr; }
        .ps-col:first-child { border-right:none; border-bottom:1px solid #f1f5f9; }
        .ps-summary { grid-template-columns:repeat(3,1fr); row-gap:10px; }
    }
    @media print {
        .emp-sidebar, header, .ps-toolbar { display:none !important; }
        .emp-content { padding:0 !important; }
        .ps-doc { box-shadow:none !important; border:none !important; }
        body { background:#fff !important; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection
