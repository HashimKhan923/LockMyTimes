@extends('layouts.employee')

@section('title', 'Loan ' . $loan->loan_number)
@section('page-title', 'Loan Detail')

@section('content')
<div class="max-w-5xl mx-auto" x-data="{ cancelOpen: false }">

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

        [$statusLbl, $statusColor, $statusBg, $statusIcon] = match($loan->status) {
            'pending'      => ['Pending review', '#92400e', '#fffbeb', 'clock'],
            'under_review' => ['In review',      '#92400e', '#fffbeb', 'eye'],
            'approved'     => ['Approved',       '#065f46', '#ecfdf5', 'check-circle'],
            'rejected'     => ['Rejected',       '#991b1b', '#fef2f2', 'x-circle'],
            'disbursed'    => ['Disbursed',      '#065f46', '#ecfdf5', 'banknote'],
            'active'       => ['Active',         '#065f46', '#ecfdf5', 'activity'],
            'completed'    => ['Fully repaid',   '#065f46', '#ecfdf5', 'flag'],
            'defaulted'    => ['Defaulted',      '#991b1b', '#fef2f2', 'alert-triangle'],
            'settled'      => ['Settled',        '#6b7280', '#f3f4f6', 'check-square'],
            'cancelled'    => ['Cancelled',      '#6b7280', '#f3f4f6', 'ban'],
            default        => [ucfirst(str_replace('_',' ',$loan->status)), '#6b7280', '#f3f4f6', 'circle'],
        };

        $canCancel = $loan->status === 'pending';
    @endphp

    {{-- Top nav --}}
    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('employee.loans.index', $tenantSlug) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-slate-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>All Loans</span>
        </a>

        @if($canCancel)
            <button @click="cancelOpen=true" type="button" class="lmt-btn-secondary lmt-btn-sm text-red-600">
                <i data-lucide="x" class="w-4 h-4"></i>
                Cancel application
            </button>
        @endif
    </div>

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
         HEADER CARD
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card mb-5" data-lmt-anim="fade-up">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold text-gray-400">{{ $loan->loan_number }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                          style="background:{{ $statusBg }};color:{{ $statusColor }};">
                        <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                        {{ $statusLbl }}
                    </span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100 mt-2"
                    style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $loan->loanType?->name ?? 'Loan' }}
                </h1>
                @if($loan->purpose)
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-2 whitespace-pre-line">{{ $loan->purpose }}</p>
                @endif

                {{-- Rejection reason --}}
                @if($loan->status === 'rejected' && $loan->rejection_reason)
                    <div class="lmt-alert lmt-alert-error mt-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                        <div>
                            <p class="font-bold text-xs uppercase tracking-wider mb-0.5">Rejection reason</p>
                            <p class="text-sm">{{ $loan->rejection_reason }}</p>
                        </div>
                    </div>
                @endif
                @if($loan->approver_comments)
                    <div class="lmt-alert lmt-alert-info mt-3">
                        <i data-lucide="message-square" class="w-5 h-5 shrink-0"></i>
                        <div>
                            <p class="font-bold text-xs uppercase tracking-wider mb-0.5">Approver comments</p>
                            <p class="text-sm">{{ $loan->approver_comments }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="text-left lg:text-right flex-shrink-0">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Principal</p>
                <p class="text-3xl lg:text-4xl font-black font-mono text-gray-900 dark:text-slate-100">
                    {{ $sym }}{{ number_format($loan->principal_amount, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $loan->tenure_months }} months @ {{ number_format($loan->interest_rate, 2) }}%
                </p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         REPAYMENT PROGRESS (only when active/completed)
    ═══════════════════════════════════════════════════════════════ --}}
    @if(in_array($loan->status, ['disbursed', 'active', 'completed', 'settled']))
        <div class="lmt-card mb-5" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">Repayment progress</h3>
                <span class="text-2xl font-black font-mono" style="color:var(--brand-600);">{{ $progress }}%</span>
            </div>

            <div class="h-3 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden mb-4">
                <div class="h-full rounded-full transition-all"
                     style="width:{{ $progress }}%; background:linear-gradient(90deg,var(--brand-500),var(--brand-600));"></div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Paid</p>
                    <p class="text-base font-black font-mono mt-1 text-emerald-600">{{ $sym }}{{ number_format($loan->amount_paid, 2) }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Remaining</p>
                    <p class="text-base font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $sym }}{{ number_format($loan->amount_remaining, 2) }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Installments</p>
                    <p class="text-base font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $loan->installments_paid }}/{{ $loan->tenure_months }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">EMI</p>
                    <p class="text-base font-black font-mono mt-1" style="color:var(--brand-600);">{{ $sym }}{{ number_format($loan->emi_amount, 2) }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-[1.4fr_1fr] gap-5">

        {{-- ═════ LEFT: details + repayment schedule ═════ --}}
        <div class="space-y-5">

            {{-- Details --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i> Loan details
                </h3>
                <div class="space-y-3">
                    @php
                        $rows = [
                            ['Principal',            $sym . number_format($loan->principal_amount, 2), 'wallet'],
                            ['Interest rate',        number_format($loan->interest_rate, 2) . '% p.a. (' . str_replace('_', ' ', $loan->interest_type) . ')', 'percent'],
                            ['Total interest',       $sym . number_format($loan->total_interest, 2), 'trending-up'],
                            ['Total amount',         $sym . number_format($loan->total_amount, 2), 'calculator'],
                            ['Monthly EMI',          $sym . number_format($loan->emi_amount, 2), 'calendar-days'],
                            ['Tenure',               $loan->tenure_months . ' months', 'clock'],
                            ['First EMI date',       $loan->first_emi_date?->format('M j, Y') ?? '—', 'calendar'],
                            ['Payroll deduction',    $loan->auto_deduct_from_payroll ? 'Yes (auto)' : 'No (manual)', 'repeat'],
                            ['Requested',            $loan->requested_at?->format('M j, Y · g:i A') ?? '—', 'send'],
                        ];
                        if ($loan->disbursed_at) {
                            $rows[] = ['Disbursed',         $loan->disbursed_at->format('M j, Y'), 'banknote'];
                            if ($loan->disbursement_method) {
                                $rows[] = ['Disbursement',  ucwords(str_replace('_', ' ', $loan->disbursement_method)) . ($loan->disbursement_reference ? ' (' . $loan->disbursement_reference . ')' : ''), 'arrow-down-circle'];
                            }
                        }
                        if ($loan->guarantor_external_name) {
                            $rows[] = ['Guarantor',         $loan->guarantor_external_name . ($loan->guarantor_external_phone ? ' · ' . $loan->guarantor_external_phone : ''), 'user-check'];
                        }
                    @endphp
                    @foreach($rows as [$label, $value, $icon])
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800 last:border-b-0">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                                <span>{{ $label }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 text-right break-words">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Repayment schedule --}}
            @if($loan->repayments->isNotEmpty())
                <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
                    <div class="p-5 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">Repayment schedule</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $loan->repayments->count() }} installment{{ $loan->repayments->count() == 1 ? '' : 's' }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="lmt-table">
                            <thead>
                                <tr>
                                    <th class="text-left">#</th>
                                    <th class="text-left">Due</th>
                                    <th class="text-right">Principal</th>
                                    <th class="text-right">Interest</th>
                                    <th class="text-right">EMI</th>
                                    <th class="text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loan->repayments as $r)
                                    @php
                                        $isOverdue = ($r->_is_overdue ?? false);
                                        [$rStatusLbl, $rStatusCls] = match(true) {
                                            $r->status === 'paid'           => ['Paid',       'lmt-badge-green'],
                                            $r->status === 'partially_paid' => ['Partial',    'lmt-badge-amber'],
                                            $r->status === 'overdue'        => ['Overdue',    'lmt-badge-red'],
                                            $r->status === 'waived'         => ['Waived',     'lmt-badge-gray'],
                                            $r->status === 'skipped'        => ['Skipped',    'lmt-badge-gray'],
                                            $isOverdue                      => ['Overdue',    'lmt-badge-red'],
                                            default                         => ['Upcoming',   'lmt-badge-gray'],
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50">
                                        <td>
                                            <span class="font-mono font-bold text-xs">{{ $r->installment_number }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs text-gray-700 dark:text-slate-200">{{ $r->due_date?->format('M j, Y') ?? '—' }}</span>
                                            @if($r->paid_date)
                                                <p class="text-[10px] text-emerald-600 font-bold mt-0.5">Paid {{ $r->paid_date->format('M j') }}</p>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <span class="font-mono text-xs text-gray-700 dark:text-slate-200">{{ $sym }}{{ number_format($r->principal_component, 2) }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="font-mono text-xs text-gray-500">{{ $sym }}{{ number_format($r->interest_component, 2) }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="font-mono font-bold text-xs text-gray-900 dark:text-slate-100">{{ $sym }}{{ number_format($r->emi_amount, 2) }}</span>
                                        </td>
                                        <td><span class="{{ $rStatusCls }}">{{ $rStatusLbl }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- ═════ RIGHT: timeline ═════ --}}
        <div class="space-y-5">
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i data-lucide="git-commit" class="w-3.5 h-3.5"></i> Activity
                </h3>

                <div class="relative">
                    @foreach($timeline as $i => $event)
                        @php
                            $colorMap = [
                                'gray'  => ['bg' => '#f3f4f6', 'fg' => '#6b7280'],
                                'brand' => ['bg' => 'var(--brand-50)', 'fg' => 'var(--brand-600)'],
                                'green' => ['bg' => '#ecfdf5', 'fg' => '#10b981'],
                                'red'   => ['bg' => '#fef2f2', 'fg' => '#ef4444'],
                                'amber' => ['bg' => '#fffbeb', 'fg' => '#d97706'],
                            ];
                            $c = $colorMap[$event['color']] ?? $colorMap['gray'];
                            $isLast = $i === count($timeline) - 1;
                        @endphp
                        <div class="relative flex gap-3 pb-4 last:pb-0">
                            @if(! $isLast)
                                <div class="absolute left-[15px] top-8 bottom-0 w-px bg-gray-200 dark:bg-slate-700"></div>
                            @endif
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 relative z-10"
                                 style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};">
                                <i data-lucide="{{ $event['icon'] }}" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-slate-100">{{ $event['title'] }}</p>
                                @if(! empty($event['detail']))
                                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 whitespace-pre-line">{{ $event['detail'] }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-1 font-semibold uppercase tracking-wider">
                                    {{ \Carbon\Carbon::parse($event['when'])->format('M j, Y · g:i A') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         CANCEL CONFIRM MODAL
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="cancelOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="cancelOpen=false">
        <div class="lmt-modal" @click.outside="cancelOpen=false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-red-50 dark:bg-red-500/10 text-red-600 flex items-center justify-center mb-4">
                    <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Cancel this application?</h3>
                <p class="text-sm text-gray-500 mt-2">
                    Loan application <span class="font-mono font-bold">{{ $loan->loan_number }}</span> will be cancelled. You can apply again if needed.
                </p>
            </div>
            <div class="flex justify-center gap-2 mt-6">
                <button @click="cancelOpen=false" class="lmt-btn-secondary">Keep it</button>
                <form action="{{ route('employee.loans.cancel', [$tenantSlug, $loan->id]) }}" method="POST">
                    @csrf
                    <button type="submit" class="lmt-btn-danger">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Cancel application
                    </button>
                </form>
            </div>
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