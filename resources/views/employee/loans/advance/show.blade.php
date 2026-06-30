@extends('layouts.employee')

@section('title', 'Advance ' . $advance->advance_number)
@section('page-title', 'Advance Detail')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ cancelOpen: false }">

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

        [$statusLbl, $statusColor, $statusBg, $statusIcon] = match($advance->status) {
            'pending'   => ['Pending review', '#92400e', '#fffbeb', 'clock'],
            'approved'  => ['Approved',       '#065f46', '#ecfdf5', 'check-circle'],
            'disbursed' => ['Disbursed',      '#065f46', '#ecfdf5', 'banknote'],
            'active'    => ['Repaying',       '#065f46', '#ecfdf5', 'activity'],
            'completed' => ['Fully repaid',   '#065f46', '#ecfdf5', 'flag'],
            'rejected'  => ['Rejected',       '#991b1b', '#fef2f2', 'x-circle'],
            'cancelled' => ['Cancelled',      '#6b7280', '#f3f4f6', 'ban'],
            default     => [ucfirst($advance->status), '#6b7280', '#f3f4f6', 'circle'],
        };

        $canCancel = $advance->status === 'pending';
    @endphp

    {{-- Top nav --}}
    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('employee.loans.index', ['tenant' => $tenantSlug, 'tab' => 'advances']) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-slate-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>All Advances</span>
        </a>

        @if($canCancel)
            <button @click="cancelOpen=true" type="button" class="lmt-btn-secondary lmt-btn-sm text-red-600">
                <i data-lucide="x" class="w-4 h-4"></i>
                Cancel request
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
         HEADER
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card mb-5" data-lmt-anim="fade-up">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold text-gray-400">{{ $advance->advance_number }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                          style="background:{{ $statusBg }};color:{{ $statusColor }};">
                        <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                        {{ $statusLbl }}
                    </span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100 mt-2"
                    style="font-family:'Plus Jakarta Sans',sans-serif">
                    Salary Advance
                </h1>
                @if($advance->reason)
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-2 whitespace-pre-line">{{ $advance->reason }}</p>
                @endif

                @if($advance->status === 'rejected' && $advance->rejection_reason)
                    <div class="lmt-alert lmt-alert-error mt-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                        <div>
                            <p class="font-bold text-xs uppercase tracking-wider mb-0.5">Rejection reason</p>
                            <p class="text-sm">{{ $advance->rejection_reason }}</p>
                        </div>
                    </div>
                @endif
                @if($advance->approver_comments)
                    <div class="lmt-alert lmt-alert-info mt-3">
                        <i data-lucide="message-square" class="w-5 h-5 shrink-0"></i>
                        <div>
                            <p class="font-bold text-xs uppercase tracking-wider mb-0.5">Approver comments</p>
                            <p class="text-sm">{{ $advance->approver_comments }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="text-left lg:text-right flex-shrink-0">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Amount</p>
                <p class="text-3xl lg:text-4xl font-black font-mono text-gray-900 dark:text-slate-100">
                    {{ $sym }}{{ number_format($advance->amount, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    @if($advance->repayment_type === 'one_time')
                        One-time deduction
                    @else
                        {{ $advance->installments_count }} × {{ $sym }}{{ number_format($advance->per_installment_amount, 2) }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Progress (when active/disbursed/completed) --}}
    @if(in_array($advance->status, ['active', 'disbursed', 'completed']))
        <div class="lmt-card mb-5" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">Repayment progress</h3>
                <span class="text-2xl font-black font-mono" style="color:var(--brand-600);">{{ $progress }}%</span>
            </div>
            <div class="h-3 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden mb-4">
                <div class="h-full rounded-full transition-all"
                     style="width:{{ $progress }}%; background:linear-gradient(90deg,var(--brand-500),var(--brand-600));"></div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Repaid</p>
                    <p class="text-base font-black font-mono mt-1 text-emerald-600">{{ $sym }}{{ number_format($advance->amount_repaid, 2) }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Remaining</p>
                    <p class="text-base font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $sym }}{{ number_format($advance->amount_remaining, 2) }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Installments</p>
                    <p class="text-base font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $advance->installments_paid }}/{{ $advance->installments_count }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-[1.4fr_1fr] gap-5">
        {{-- ═════ LEFT: details + deductions ═════ --}}
        <div class="space-y-5">

            {{-- Details --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i> Advance details
                </h3>
                <div class="space-y-3">
                    @php
                        $rows = [
                            ['Amount',                $sym . number_format($advance->amount, 2),                          'wallet'],
                            ['Repayment type',        $advance->repayment_type === 'one_time' ? 'One-time' : 'Installments', 'repeat'],
                            ['Installments',          $advance->installments_count,                                       'list'],
                            ['Per installment',       $sym . number_format($advance->per_installment_amount, 2),          'minus-circle'],
                            ['First deduction',       $advance->first_deduction_date?->format('M j, Y') ?? '—',           'calendar'],
                            ['Requested',             $advance->created_at->format('M j, Y · g:i A'),                     'send'],
                        ];
                        if ($advance->disbursed_at) {
                            $rows[] = ['Disbursed', $advance->disbursed_at->format('M j, Y'), 'banknote'];
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

            {{-- Deductions list --}}
            @if($advance->deductions->isNotEmpty())
                <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
                    <div class="p-5 border-b border-gray-100 dark:border-slate-700">
                        <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">Deduction schedule</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $advance->deductions->count() }} deduction{{ $advance->deductions->count() == 1 ? '' : 's' }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="lmt-table">
                            <thead>
                                <tr>
                                    <th class="text-left">#</th>
                                    <th class="text-left">Date</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($advance->deductions as $d)
                                    @php
                                        [$dStatusLbl, $dStatusCls] = match($d->status) {
                                            'deducted' => ['Deducted', 'lmt-badge-green'],
                                            'skipped'  => ['Skipped',  'lmt-badge-gray'],
                                            'waived'   => ['Waived',   'lmt-badge-gray'],
                                            default    => ['Pending',  'lmt-badge-amber'],
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="font-mono font-bold text-xs">{{ $d->deduction_number }}</span></td>
                                        <td><span class="text-xs text-gray-700 dark:text-slate-200">{{ $d->deduction_date?->format('M j, Y') ?? '—' }}</span></td>
                                        <td class="text-right">
                                            <span class="font-mono font-bold text-xs text-gray-900 dark:text-slate-100">{{ $sym }}{{ number_format($d->amount, 2) }}</span>
                                        </td>
                                        <td><span class="{{ $dStatusCls }}">{{ $dStatusLbl }}</span></td>
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

    {{-- Cancel modal --}}
    <div x-show="cancelOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="cancelOpen=false">
        <div class="lmt-modal" @click.outside="cancelOpen=false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-red-50 dark:bg-red-500/10 text-red-600 flex items-center justify-center mb-4">
                    <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Cancel this request?</h3>
                <p class="text-sm text-gray-500 mt-2">
                    Advance request <span class="font-mono font-bold">{{ $advance->advance_number }}</span> will be cancelled.
                </p>
            </div>
            <div class="flex justify-center gap-2 mt-6">
                <button @click="cancelOpen=false" class="lmt-btn-secondary">Keep it</button>
                <form action="{{ route('employee.loans.advance.cancel', [$tenantSlug, $advance->id]) }}" method="POST">
                    @csrf
                    <button type="submit" class="lmt-btn-danger">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Cancel request
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