@extends('layouts.employee')

@section('title', 'Expense ' . $expense->expense_number)
@section('page-title', 'Expense Detail')

@section('content')
<div class="max-w-4xl mx-auto" x-data="expenseShow()">

    @php
        $cur = $expense->currency ?? ($tenantCurrency ?? 'USD');
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

        [$statusLbl, $statusColor, $statusBg, $statusIcon] = match($expense->status) {
            'draft'     => ['Draft',     '#6b7280', '#f3f4f6', 'file-edit'],
            'submitted' => ['Pending',   '#92400e', '#fffbeb', 'clock'],
            'approved'  => ['Approved',  '#065f46', '#ecfdf5', 'check-circle'],
            'rejected'  => ['Rejected',  '#991b1b', '#fef2f2', 'x-circle'],
            'paid'      => ['Paid',      '#065f46', '#ecfdf5', 'banknote'],
            'cancelled' => ['Cancelled', '#6b7280', '#f3f4f6', 'ban'],
            default     => [ucfirst($expense->status), '#6b7280', '#f3f4f6', 'circle'],
        };

        $isImage = $expense->receipt_path && preg_match('/\.(jpe?g|png|gif|webp)$/i', $expense->receipt_path);
        $isPdf   = $expense->receipt_path && preg_match('/\.pdf$/i', $expense->receipt_path);

        $canSubmit = $expense->status === 'draft';
        $canDelete = in_array($expense->status, ['draft', 'submitted']);

        $miles = (float) ($expense->miles ?? 0);
        $rate  = (float) ($expense->mileage_rate ?? 0);
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         TOP NAV BAR
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <a href="{{ route('employee.expenses.index', $tenantSlug) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-slate-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>All Expenses</span>
        </a>

        <div class="flex items-center gap-2">
            @if($canSubmit)
                <form action="{{ route('employee.expenses.submit', [$tenantSlug, $expense->id]) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Submit for approval
                    </button>
                </form>
            @endif
            @if($canDelete)
                <button @click="deleteOpen=true" type="button" class="lmt-btn-secondary lmt-btn-sm text-red-600">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete
                </button>
            @endif
        </div>
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
         HEADER CARD — amount + status + meta
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card mb-5" data-lmt-anim="fade-up">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold text-gray-400">{{ $expense->expense_number }}</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                          style="background:{{ $statusBg }};color:{{ $statusColor }};">
                        <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                        {{ $statusLbl }}
                    </span>
                    @if($expense->is_mileage)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                            <i data-lucide="car" class="w-3 h-3"></i> Mileage
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100 mt-2"
                    style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $expense->title }}
                </h1>
                @if($expense->description)
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-2 whitespace-pre-line">{{ $expense->description }}</p>
                @endif

                {{-- Rejection reason callout --}}
                @if($expense->status === 'rejected' && $expense->rejection_reason)
                    <div class="lmt-alert lmt-alert-error mt-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                        <div>
                            <p class="font-bold text-xs uppercase tracking-wider mb-0.5">Rejection reason</p>
                            <p class="text-sm">{{ $expense->rejection_reason }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="text-left lg:text-right flex-shrink-0">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Amount</p>
                <p class="text-3xl lg:text-4xl font-black font-mono text-gray-900 dark:text-slate-100">
                    {{ $sym }}{{ number_format($expense->amount, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5 font-mono uppercase">{{ $expense->currency }}</p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1.4fr_1fr] gap-5">

        {{-- ═════ LEFT: details + receipt ═════ --}}
        <div class="space-y-5">

            {{-- Details card --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i> Details
                </h3>
                <div class="space-y-3">
                    @php
                        $rows = [
                            ['Category',      $expense->category?->name ?? '—', 'tag'],
                            ['Expense date',  $expense->expense_date?->format('F j, Y') ?? '—', 'calendar'],
                            ['Merchant',      $expense->merchant ?? '—', 'store'],
                            ['Payment method', $expense->payment_method ?? '—', 'credit-card'],
                            ['Project code',  $expense->project_code ?? '—', 'folder'],
                            ['Submitted',     $expense->submitted_at?->format('M j, Y g:i A') ?? '—', 'send'],
                        ];
                        if ($expense->is_mileage) {
                            $rows[] = ['Miles driven', $miles > 0 ? number_format($miles, 1) . ' mi' : '—', 'route'];
                            $rows[] = ['Rate per mile', $rate > 0 ? $sym . number_format($rate, 4) : '—', 'gauge'];
                            $computed = $miles * $rate;
                            if ($computed > 0) {
                                $rows[] = ['Computed mileage', $sym . number_format($computed, 2), 'calculator'];
                            }
                        }
                    @endphp
                    @foreach($rows as [$label, $value, $icon])
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800 last:border-b-0">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                                <span>{{ $label }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 text-right break-words">
                                {{ $value }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Receipt card --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="paperclip" class="w-3.5 h-3.5"></i> Receipt
                    </h3>
                    @if($expense->receipt_path)
                        <a href="{{ route('employee.expenses.receipt', [$tenantSlug, $expense->id]) }}"
                           target="_blank" rel="noopener"
                           class="text-xs font-bold transition-colors hover:underline"
                           style="color:var(--brand-500);">
                            <i data-lucide="external-link" class="w-3 h-3 inline"></i>
                            Open original
                        </a>
                    @endif
                </div>

                @if(! $expense->receipt_path)
                    <div class="text-center py-8">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-2">
                            <i data-lucide="image-off" class="w-5 h-5 text-gray-300"></i>
                        </div>
                        <p class="text-xs text-gray-500">No receipt attached.</p>
                    </div>
                @elseif($isImage)
                    <a href="{{ route('employee.expenses.receipt', [$tenantSlug, $expense->id]) }}"
                       target="_blank" rel="noopener"
                       class="block rounded-xl overflow-hidden border border-gray-100 dark:border-slate-700 hover:opacity-90 transition-opacity">
                        <img src="{{ route('employee.expenses.receipt', [$tenantSlug, $expense->id]) }}"
                             alt="Receipt"
                             class="w-full h-auto max-h-96 object-contain bg-gray-50 dark:bg-slate-800"/>
                    </a>
                @elseif($isPdf)
                    <a href="{{ route('employee.expenses.receipt', [$tenantSlug, $expense->id]) }}"
                       target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 text-red-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="file-text" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm text-gray-900 dark:text-slate-100">PDF receipt</p>
                            <p class="text-xs text-gray-500 truncate">{{ basename($expense->receipt_path) }}</p>
                        </div>
                        <i data-lucide="external-link" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                    </a>
                @else
                    <a href="{{ route('employee.expenses.receipt', [$tenantSlug, $expense->id]) }}"
                       target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-slate-800 text-gray-500 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="file" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm text-gray-900 dark:text-slate-100">Attachment</p>
                            <p class="text-xs text-gray-500 truncate">{{ basename($expense->receipt_path) }}</p>
                        </div>
                        <i data-lucide="external-link" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                    </a>
                @endif
            </div>
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

            {{-- Approvals (if multiple levels) --}}
            @if($approvals->isNotEmpty())
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i> Approval chain
                    </h3>
                    <div class="space-y-2.5">
                        @foreach($approvals as $a)
                            @php
                                [$decisionLbl, $decisionCls] = match($a->decision) {
                                    'approved' => ['Approved', 'lmt-badge-green'],
                                    'rejected' => ['Rejected', 'lmt-badge-red'],
                                    default    => ['Pending',  'lmt-badge-amber'],
                                };
                            @endphp
                            <div class="flex items-center gap-3 p-2.5 rounded-xl bg-gray-50/70 dark:bg-slate-800/50">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black text-white flex-shrink-0"
                                     style="background:var(--brand-500);">
                                    L{{ $a->approval_level }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">
                                        {{ $a->approver->name ?? 'Approver #' . $a->approver_id }}
                                    </p>
                                    @if($a->decided_at)
                                        <p class="text-[10px] text-gray-400">
                                            {{ \Carbon\Carbon::parse($a->decided_at)->format('M j, Y') }}
                                        </p>
                                    @endif
                                </div>
                                <span class="{{ $decisionCls }}">{{ $decisionLbl }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         DELETE CONFIRM MODAL
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="deleteOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="deleteOpen=false">
        <div class="lmt-modal" @click.outside="deleteOpen=false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-red-50 dark:bg-red-500/10 text-red-600 flex items-center justify-center mb-4">
                    <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Delete this expense?</h3>
                <p class="text-sm text-gray-500 mt-2">
                    Expense <span class="font-mono font-bold">{{ $expense->expense_number }}</span> will be permanently deleted. This cannot be undone.
                </p>
            </div>
            <div class="flex justify-center gap-2 mt-6">
                <button @click="deleteOpen=false" class="lmt-btn-secondary">Keep it</button>
                <form action="{{ route('employee.expenses.destroy', [$tenantSlug, $expense->id]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="lmt-btn-danger" :disabled="deleteSubmitting" @click="deleteSubmitting=true">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span x-show="!deleteSubmitting">Delete</span>
                        <span x-show="deleteSubmitting">Deleting…</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function expenseShow() {
    return {
        deleteOpen: false,
        deleteSubmitting: false,
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection