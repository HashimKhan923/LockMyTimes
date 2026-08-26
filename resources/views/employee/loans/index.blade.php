@extends('layouts.employee')

@section('title', 'Loans & Advances')
@section('page-title', 'Loans & Advances')

@section('content')
<div x-data="{ tab: '{{ $tab }}' }">

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
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         HERO — Outstanding balance + active loan + next EMI
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 grid lg:grid-cols-[1.4fr_1fr] gap-6 items-center">
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Total outstanding</p>
                <h1 class="text-white text-2xl lg:text-3xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $sym }}{{ number_format($loanStats->total_outstanding + $advanceStats->total_outstanding, 2) }}
                </h1>
                <p class="text-white/75 text-sm mt-1.5">
                    {{ (int) $loanStats->active }} active loan{{ $loanStats->active == 1 ? '' : 's' }} &middot;
                    {{ (int) $advanceStats->active }} active advance{{ $advanceStats->active == 1 ? '' : 's' }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('employee.loans.create', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white text-gray-900 hover:bg-white/95 transition-all"
                       style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                        <i data-lucide="hand-coins" class="w-4 h-4"></i> Apply for Loan
                    </a>
                    <a href="{{ route('employee.loans.advance.create', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white/15 border border-white/25 text-white hover:bg-white/25 transition-all">
                        <i data-lucide="zap" class="w-4 h-4"></i> Request Advance
                    </a>
                </div>
            </div>

            {{-- Next EMI card --}}
            @if($activeLoan && $nextEmi)
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Next EMI due</p>
                        @if($nextEmi->due_date->isPast())
                            <span class="px-2 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold uppercase">Overdue</span>
                        @elseif($nextEmi->due_date->lte(now()->addDays(7)))
                            <span class="px-2 py-0.5 rounded-full bg-amber-400 text-amber-900 text-[10px] font-bold uppercase">Due soon</span>
                        @endif
                    </div>
                    <p class="text-white text-2xl font-black font-mono">
                        {{ $sym }}{{ number_format($nextEmi->emi_amount, 2) }}
                    </p>
                    <p class="text-white/70 text-xs mt-1">
                        Due {{ $nextEmi->due_date->format('M j, Y') }} &middot;
                        Installment {{ $nextEmi->installment_number }} of {{ $activeLoan->tenure_months }}
                    </p>
                    <a href="{{ route('employee.loans.show', [$tenantSlug, $activeLoan->id]) }}"
                       class="inline-flex items-center gap-1.5 mt-3 text-xs font-bold text-white/90 hover:text-white">
                        View loan {{ $activeLoan->loan_number }}
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            @elseif($activeLoan)
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-5">
                    <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Active loan</p>
                    <p class="text-white text-lg font-black mt-1">{{ $activeLoan->loan_number }}</p>
                    <p class="text-white/75 text-sm">{{ $activeLoan->loanType?->name }}</p>
                    <a href="{{ route('employee.loans.show', [$tenantSlug, $activeLoan->id]) }}"
                       class="inline-flex items-center gap-1.5 mt-3 text-xs font-bold text-white/90 hover:text-white">
                        View details
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            @else
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-5">
                    <p class="text-white/70 text-xs font-bold uppercase tracking-wider">No active loans</p>
                    <p class="text-white/85 text-sm mt-2 leading-relaxed">
                        Apply for a loan or request a salary advance to get started.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="lmt-alert lmt-alert-success mb-5">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         TABS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-1 mb-5 border-b border-gray-200 dark:border-slate-700">
        <button @click="tab='loans'"
                :class="tab==='loans' ? 'text-white' : 'text-gray-800 hover:text-gray-800 dark:hover:text-slate-300'"
                class="px-4 py-2.5 text-sm font-bold rounded-t-xl -mb-px transition-all relative"
                :style="tab==='loans' ? 'background:var(--brand-500);' : ''">
            <i data-lucide="hand-coins" class="w-4 h-4 inline -mt-0.5"></i>
            Loans
            <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="tab==='loans' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-800'">
                {{ (int) $loanStats->total }}
            </span>
        </button>
        <button @click="tab='advances'"
                :class="tab==='advances' ? 'text-white' : 'text-gray-800 hover:text-gray-800 dark:hover:text-slate-300'"
                class="px-4 py-2.5 text-sm font-bold rounded-t-xl -mb-px transition-all relative"
                :style="tab==='advances' ? 'background:var(--brand-500);' : ''">
            <i data-lucide="zap" class="w-4 h-4 inline -mt-0.5"></i>
            Salary Advances
            <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="tab==='advances' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-800'">
                {{ (int) $advanceStats->total }}
            </span>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         LOANS TAB
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='loans'" x-cloak>

        {{-- Loan stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            @php
                $statCards = [
                    ['label' => 'Total loans',     'value' => (int) $loanStats->total,             'icon' => 'list',            'color' => 'gray'],
                    ['label' => 'Pending',         'value' => (int) $loanStats->pending,           'icon' => 'clock',           'color' => 'amber'],
                    ['label' => 'Active',          'value' => (int) $loanStats->active,            'icon' => 'activity',        'color' => 'brand'],
                    ['label' => 'Completed',       'value' => (int) $loanStats->completed,         'icon' => 'check-circle',    'color' => 'green'],
                ];
                $colorClasses = [
                    'gray'  => ['bg' => 'bg-gray-100 dark:bg-slate-700', 'fg' => 'text-gray-800'],
                    'amber' => ['bg' => 'bg-amber-50 dark:bg-amber-500/15', 'fg' => 'text-amber-600'],
                    'brand' => ['bg' => '', 'fg' => '', 'inline_bg' => 'background:var(--brand-50);', 'inline_fg' => 'color:var(--brand-600);'],
                    'green' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/15', 'fg' => 'text-emerald-600'],
                ];
            @endphp
            @foreach($statCards as $card)
                @php $c = $colorClasses[$card['color']]; @endphp
                <div class="lmt-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">{{ $card['label'] }}</p>
                            <p class="text-2xl font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $card['value'] }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-xl {{ $c['bg'] ?? '' }} {{ $c['fg'] ?? '' }} flex items-center justify-center"
                             @if(! empty($c['inline_bg'])) style="{{ $c['inline_bg'] }}{{ $c['inline_fg'] ?? '' }}" @endif>
                            <i data-lucide="{{ $card['icon'] }}" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Loans table --}}
        <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">My Loans</h2>
                    <p class="text-xs text-gray-800 mt-0.5">All your loan applications and active loans</p>
                </div>
                <a href="{{ route('employee.loans.create', $tenantSlug) }}" class="lmt-btn-primary lmt-btn-sm hidden sm:inline-flex">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Apply
                </a>
            </div>

            @if($loans->isEmpty())
                <div class="text-center py-16 px-5">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                        <i data-lucide="hand-coins" class="w-7 h-7 text-gray-800"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-800 dark:text-slate-200">No loans yet</p>
                    <p class="text-xs text-gray-800 mt-1">Apply for your first loan to see it here.</p>
                    <a href="{{ route('employee.loans.create', $tenantSlug) }}" class="lmt-btn-primary lmt-btn-sm mt-4 inline-flex">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Apply for Loan
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="lmt-table">
                        <thead>
                            <tr>
                                <th class="text-left">Loan #</th>
                                <th class="text-left">Type</th>
                                <th class="text-right">Principal</th>
                                <th class="text-right hidden md:table-cell">EMI</th>
                                <th class="text-left hidden lg:table-cell">Progress</th>
                                <th class="text-left">Status</th>
                                <th class="text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $ln)
                                @php
                                    [$statusLbl, $statusCls] = match($ln->status) {
                                        'pending'      => ['Pending',     'lmt-badge-amber'],
                                        'under_review' => ['In review',   'lmt-badge-amber'],
                                        'approved'     => ['Approved',    'lmt-badge-green'],
                                        'disbursed'    => ['Disbursed',   'lmt-badge-green'],
                                        'active'       => ['Active',      'lmt-badge-green'],
                                        'completed'    => ['Completed',   'lmt-badge-gray'],
                                        'rejected'     => ['Rejected',    'lmt-badge-red'],
                                        'cancelled'    => ['Cancelled',   'lmt-badge-gray'],
                                        'defaulted'    => ['Defaulted',   'lmt-badge-red'],
                                        'settled'      => ['Settled',     'lmt-badge-gray'],
                                        default        => [ucfirst(str_replace('_',' ',$ln->status)), 'lmt-badge-gray'],
                                    };
                                    $lnProgress = $ln->total_amount > 0
                                        ? round(((float) $ln->amount_paid / (float) $ln->total_amount) * 100, 1)
                                        : 0;
                                @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50">
                                    <td>
                                        <a href="{{ route('employee.loans.show', [$tenantSlug, $ln->id]) }}"
                                           class="font-mono text-xs font-bold hover:underline" style="color:var(--brand-600);">
                                            {{ $ln->loan_number }}
                                        </a>
                                        <p class="text-[10px] text-gray-800 mt-0.5">{{ $ln->created_at->format('M j, Y') }}</p>
                                    </td>
                                    <td>
                                        <span class="text-sm font-bold text-gray-900 dark:text-slate-100">{{ $ln->loanType?->name ?? '—' }}</span>
                                        @if($ln->tenure_months)
                                            <p class="text-[10px] text-gray-800">{{ $ln->tenure_months }} months @ {{ number_format($ln->interest_rate, 2) }}%</p>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <span class="font-mono font-bold text-sm text-gray-900 dark:text-slate-100">
                                            {{ $sym }}{{ number_format($ln->principal_amount, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-right hidden md:table-cell">
                                        <span class="font-mono text-sm text-gray-800 dark:text-slate-200">
                                            {{ $sym }}{{ number_format($ln->emi_amount, 2) }}
                                        </span>
                                    </td>
                                    <td class="hidden lg:table-cell" style="min-width:160px;">
                                        @if(in_array($ln->status, ['disbursed', 'active', 'completed']))
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                                                    <div class="h-full rounded-full transition-all"
                                                         style="width:{{ $lnProgress }}%; background:linear-gradient(90deg,var(--brand-500),var(--brand-600));"></div>
                                                </div>
                                                <span class="text-[10px] font-bold text-gray-800 flex-shrink-0">{{ $lnProgress }}%</span>
                                            </div>
                                            <p class="text-[10px] text-gray-800 mt-1">{{ $ln->installments_paid }}/{{ $ln->tenure_months }} paid</p>
                                        @else
                                            <span class="text-xs text-gray-800">—</span>
                                        @endif
                                    </td>
                                    <td><span class="{{ $statusCls }}">{{ $statusLbl }}</span></td>
                                    <td class="text-right">
                                        <a href="{{ route('employee.loans.show', [$tenantSlug, $ln->id]) }}"
                                           class="text-xs font-bold transition-colors hover:underline px-2 py-1"
                                           style="color:var(--brand-500);">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         ADVANCES TAB
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='advances'" x-cloak>

        {{-- Advance stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            @php
                $advCards = [
                    ['label' => 'Total requests', 'value' => (int) $advanceStats->total,     'icon' => 'list',         'color' => 'gray'],
                    ['label' => 'Pending',        'value' => (int) $advanceStats->pending,   'icon' => 'clock',        'color' => 'amber'],
                    ['label' => 'Active',         'value' => (int) $advanceStats->active,    'icon' => 'activity',     'color' => 'brand'],
                    ['label' => 'Completed',      'value' => (int) $advanceStats->completed, 'icon' => 'check-circle', 'color' => 'green'],
                ];
            @endphp
            @foreach($advCards as $card)
                @php $c = $colorClasses[$card['color']]; @endphp
                <div class="lmt-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">{{ $card['label'] }}</p>
                            <p class="text-2xl font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $card['value'] }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-xl {{ $c['bg'] ?? '' }} {{ $c['fg'] ?? '' }} flex items-center justify-center"
                             @if(! empty($c['inline_bg'])) style="{{ $c['inline_bg'] }}{{ $c['inline_fg'] ?? '' }}" @endif>
                            <i data-lucide="{{ $card['icon'] }}" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Advances table --}}
        <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">My Salary Advances</h2>
                    <p class="text-xs text-gray-800 mt-0.5">Quick advances repaid through payroll deductions</p>
                </div>
                <a href="{{ route('employee.loans.advance.create', $tenantSlug) }}" class="lmt-btn-primary lmt-btn-sm hidden sm:inline-flex">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Request
                </a>
            </div>

            @if($advances->isEmpty())
                <div class="text-center py-16 px-5">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                        <i data-lucide="zap" class="w-7 h-7 text-gray-800"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-800 dark:text-slate-200">No advances yet</p>
                    <p class="text-xs text-gray-800 mt-1">Request your first salary advance to see it here.</p>
                    <a href="{{ route('employee.loans.advance.create', $tenantSlug) }}" class="lmt-btn-primary lmt-btn-sm mt-4 inline-flex">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Request Advance
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="lmt-table">
                        <thead>
                            <tr>
                                <th class="text-left">Advance #</th>
                                <th class="text-right">Amount</th>
                                <th class="text-left hidden md:table-cell">Repayment</th>
                                <th class="text-left hidden lg:table-cell">Progress</th>
                                <th class="text-left">Status</th>
                                <th class="text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($advances as $adv)
                                @php
                                    [$advStatusLbl, $advStatusCls] = match($adv->status) {
                                        'pending'   => ['Pending',   'lmt-badge-amber'],
                                        'approved'  => ['Approved',  'lmt-badge-green'],
                                        'disbursed' => ['Disbursed', 'lmt-badge-green'],
                                        'active'    => ['Active',    'lmt-badge-green'],
                                        'completed' => ['Completed', 'lmt-badge-gray'],
                                        'rejected'  => ['Rejected',  'lmt-badge-red'],
                                        'cancelled' => ['Cancelled', 'lmt-badge-gray'],
                                        default     => [ucfirst($adv->status), 'lmt-badge-gray'],
                                    };
                                    $advProgress = $adv->amount > 0
                                        ? round(((float) $adv->amount_repaid / (float) $adv->amount) * 100, 1)
                                        : 0;
                                @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50">
                                    <td>
                                        <a href="{{ route('employee.loans.advance.show', [$tenantSlug, $adv->id]) }}"
                                           class="font-mono text-xs font-bold hover:underline" style="color:var(--brand-600);">
                                            {{ $adv->advance_number }}
                                        </a>
                                        <p class="text-[10px] text-gray-800 mt-0.5">{{ $adv->created_at->format('M j, Y') }}</p>
                                    </td>
                                    <td class="text-right">
                                        <span class="font-mono font-bold text-sm text-gray-900 dark:text-slate-100">
                                            {{ $sym }}{{ number_format($adv->amount, 2) }}
                                        </span>
                                    </td>
                                    <td class="hidden md:table-cell">
                                        @if($adv->repayment_type === 'one_time')
                                            <span class="text-xs text-gray-800 dark:text-slate-200">One-time deduction</span>
                                        @else
                                            <span class="text-xs text-gray-800 dark:text-slate-200">
                                                {{ $adv->installments_count }} × {{ $sym }}{{ number_format($adv->per_installment_amount, 2) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="hidden lg:table-cell" style="min-width:160px;">
                                        @if(in_array($adv->status, ['active', 'disbursed', 'completed']))
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                                                    <div class="h-full rounded-full transition-all"
                                                         style="width:{{ $advProgress }}%; background:linear-gradient(90deg,var(--brand-500),var(--brand-600));"></div>
                                                </div>
                                                <span class="text-[10px] font-bold text-gray-800 flex-shrink-0">{{ $advProgress }}%</span>
                                            </div>
                                            <p class="text-[10px] text-gray-800 mt-1">{{ $adv->installments_paid }}/{{ $adv->installments_count }} paid</p>
                                        @else
                                            <span class="text-xs text-gray-800">—</span>
                                        @endif
                                    </td>
                                    <td><span class="{{ $advStatusCls }}">{{ $advStatusLbl }}</span></td>
                                    <td class="text-right">
                                        <a href="{{ route('employee.loans.advance.show', [$tenantSlug, $adv->id]) }}"
                                           class="text-xs font-bold transition-colors hover:underline px-2 py-1"
                                           style="color:var(--brand-500);">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                    {{ $advances->links() }}
                </div>
            @endif
        </div>
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