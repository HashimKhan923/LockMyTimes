@extends('layouts.employee')

@section('title', 'Expense Approvals')
@section('page-title', 'Expense Approvals')

@section('content')
<div x-data="expenseApprovals()">

    @php
        $cur = $tenantCurrency ?? 'USD';
        $sym = match($cur) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€', 'GBP' => '£', 'JPY', 'CNY' => '¥',
            'INR' => '₹', 'PKR' => 'Rs',
            default => $cur.' ',
        };
    @endphp

    {{-- Back nav --}}
    <a href="{{ route('employee.team.index', $tenantSlug) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 dark:hover:text-slate-200 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Back to team</span>
    </a>

    <div class="mb-5 flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
                Expense Approvals
            </h1>
            <p class="text-sm text-gray-800 mt-1">Review reimbursement claims from your team.</p>
        </div>
        @if($pendingTotal > 0)
            <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-2xl px-4 py-3">
                <p class="text-[10px] text-amber-700 dark:text-amber-300 font-bold uppercase tracking-wider">Pending total</p>
                <p class="text-lg font-black font-mono text-amber-700 dark:text-amber-300">{{ $sym }}{{ number_format($pendingTotal, 2) }}</p>
            </div>
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

    {{-- Status filters --}}
    <div class="flex flex-wrap items-center gap-1.5 mb-5">
        @php
            $chips = [
                ['key' => 'submitted', 'label' => 'Pending',  'count' => (int) ($counters->submitted ?? 0)],
                ['key' => 'approved',  'label' => 'Approved', 'count' => (int) ($counters->approved ?? 0)],
                ['key' => 'paid',      'label' => 'Paid',     'count' => (int) ($counters->paid ?? 0)],
                ['key' => 'rejected',  'label' => 'Rejected', 'count' => (int) ($counters->rejected ?? 0)],
                ['key' => 'all',       'label' => 'All',      'count' => (int) ($counters->total ?? 0)],
            ];
        @endphp
        @foreach($chips as $chip)
            @php $active = $status === $chip['key']; @endphp
            <a href="{{ route('employee.team.approvals.expenses', ['tenant' => $tenantSlug, 'status' => $chip['key']]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all
                      {{ $active
                            ? 'border-transparent text-white'
                            : 'border-gray-200 dark:border-slate-700 text-gray-800 hover:border-gray-300' }}"
               @if($active) style="background:var(--brand-500);" @endif>
                {{ $chip['label'] }}
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $active ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">
                    {{ $chip['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- Expense list --}}
    @if($expenses->isEmpty())
        <div class="lmt-card text-center py-16 px-5" data-lmt-anim="fade-up">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                <i data-lucide="receipt" class="w-7 h-7 text-gray-800"></i>
            </div>
            <p class="text-sm font-bold text-gray-800 dark:text-slate-200">
                @if($status === 'submitted') Nothing to approve right now @else No expenses in this view @endif
            </p>
            <p class="text-xs text-gray-800 mt-1">
                @if($status === 'submitted') You're all caught up. @else Try another filter. @endif
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($expenses as $e)
                @php
                    [$eLbl, $eColor, $eBg, $eIcon] = match($e->status) {
                        'submitted' => ['Pending review', '#92400e', '#fffbeb', 'clock'],
                        'approved'  => ['Approved',       '#065f46', '#ecfdf5', 'check-circle'],
                        'paid'      => ['Paid',           '#065f46', '#ecfdf5', 'banknote'],
                        'rejected'  => ['Rejected',       '#991b1b', '#fef2f2', 'x-circle'],
                        'cancelled' => ['Cancelled',      '#6b7280', '#f3f4f6', 'ban'],
                        default     => [ucfirst($e->status), '#6b7280', '#f3f4f6', 'circle'],
                    };
                @endphp

                <div class="lmt-card" data-lmt-anim="fade-up">
                    <div class="flex flex-col lg:flex-row gap-4">

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start gap-3">
                                <img src="{{ $e->employee?->avatar_url }}" alt="{{ $e->employee?->full_name }}"
                                     class="w-10 h-10 rounded-full ring-2 ring-white dark:ring-slate-700 object-cover flex-shrink-0"/>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('employee.team.show', [$tenantSlug, $e->employee_id]) }}"
                                           class="font-bold text-sm text-gray-900 dark:text-slate-100 hover:underline">
                                            {{ $e->employee?->full_name }}
                                        </a>
                                        <span class="font-mono text-[10px] text-gray-800">{{ $e->expense_number }}</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                              style="background:{{ $eBg }};color:{{ $eColor }};">
                                            <i data-lucide="{{ $eIcon }}" class="w-2.5 h-2.5"></i>
                                            {{ $eLbl }}
                                        </span>
                                        @if($e->is_mileage)
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 uppercase">Mileage</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-800 mt-0.5">{{ $e->employee?->position?->title ?? 'No position' }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Amount</p>
                                    <p class="text-lg font-black font-mono text-gray-900 dark:text-slate-100">{{ $sym }}{{ number_format($e->amount, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-3 grid sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Title</p>
                                    <p class="font-semibold text-gray-900 dark:text-slate-100 mt-0.5 truncate">{{ $e->title }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Category</p>
                                    <p class="font-semibold text-gray-900 dark:text-slate-100 mt-0.5">{{ $e->category?->name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Date</p>
                                    <p class="font-semibold text-gray-900 dark:text-slate-100 mt-0.5">{{ $e->expense_date?->format('M j, Y') ?? '—' }}</p>
                                </div>
                                @if($e->merchant)
                                    <div>
                                        <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Merchant</p>
                                        <p class="font-semibold text-gray-900 dark:text-slate-100 mt-0.5">{{ $e->merchant }}</p>
                                    </div>
                                @endif
                                @if($e->payment_method)
                                    <div>
                                        <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Payment</p>
                                        <p class="font-semibold text-gray-900 dark:text-slate-100 mt-0.5">{{ $e->payment_method }}</p>
                                    </div>
                                @endif
                                @if($e->project_code)
                                    <div>
                                        <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Project</p>
                                        <p class="font-semibold font-mono text-gray-900 dark:text-slate-100 mt-0.5">{{ $e->project_code }}</p>
                                    </div>
                                @endif
                            </div>

                            @if($e->description)
                                <div class="mt-3">
                                    <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Description</p>
                                    <p class="text-xs text-gray-800 dark:text-slate-200 mt-1 whitespace-pre-line">{{ $e->description }}</p>
                                </div>
                            @endif

                            @if($e->receipt_path)
                                <div class="mt-3">
                                    <a href="{{ route('employee.expenses.receipt', [$tenantSlug, $e->id]) }}"
                                       target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1.5 text-xs font-bold hover:underline" style="color:var(--brand-500);">
                                        <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                        View receipt
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                    </a>
                                </div>
                            @endif

                            @if($e->status === 'rejected' && $e->rejection_reason)
                                <div class="lmt-alert lmt-alert-error mt-3">
                                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                                    <p class="text-xs"><span class="font-bold">Rejection:</span> {{ $e->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Action buttons (only for submitted) --}}
                        @if($e->status === 'submitted')
                            <div class="lg:w-48 flex-shrink-0 flex flex-col gap-2">
                                <button @click="openApprove({{ $e->id }})" class="lmt-btn-primary lmt-btn-sm w-full justify-center">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    Approve
                                </button>
                                <button @click="openReject({{ $e->id }})" class="lmt-btn-secondary lmt-btn-sm text-red-600 w-full justify-center">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                    Reject
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $expenses->links() }}
        </div>
    @endif

    {{-- APPROVE MODAL --}}
    <div x-show="approveOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="approveOpen=false">
        <div class="lmt-modal" @click.outside="approveOpen=false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 flex items-center justify-center mb-4">
                    <i data-lucide="check-circle" class="w-7 h-7"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Approve this expense?</h3>
                <p class="text-sm text-gray-800 mt-2">An optional note will be visible to the employee.</p>
            </div>
            <form :action="approveUrl" method="POST" class="mt-5">
                @csrf
                @method('PATCH')
                <textarea name="note" maxlength="500" rows="3"
                          placeholder="Optional note for the employee…"
                          class="lmt-textarea"></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="approveOpen=false" class="lmt-btn-secondary">Cancel</button>
                    <button type="submit" class="lmt-btn-primary" :disabled="submitting" @click="submitting=true">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span x-show="!submitting">Approve</span>
                        <span x-show="submitting">Approving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- REJECT MODAL --}}
    <div x-show="rejectOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="rejectOpen=false">
        <div class="lmt-modal" @click.outside="rejectOpen=false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-red-50 dark:bg-red-500/10 text-red-600 flex items-center justify-center mb-4">
                    <i data-lucide="x-circle" class="w-7 h-7"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Reject this expense?</h3>
                <p class="text-sm text-gray-800 mt-2">A reason is required and will be visible to the employee.</p>
            </div>
            <form :action="rejectUrl" method="POST" class="mt-5">
                @csrf
                @method('PATCH')
                <textarea name="reason" required minlength="5" maxlength="500" rows="3"
                          placeholder="Explain why this expense is being rejected…"
                          class="lmt-textarea"></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="rejectOpen=false" class="lmt-btn-secondary">Cancel</button>
                    <button type="submit" class="lmt-btn-danger" :disabled="submitting" @click="submitting=true">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        <span x-show="!submitting">Reject</span>
                        <span x-show="submitting">Rejecting…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function expenseApprovals() {
    return {
        approveOpen: false,
        rejectOpen: false,
        submitting: false,
        currentId: null,
        approveUrlTemplate: "{{ rtrim(route('employee.team.expenses.approve', [$tenantSlug, 'PLACEHOLDER']), '/') }}",
        rejectUrlTemplate:  "{{ rtrim(route('employee.team.expenses.reject',  [$tenantSlug, 'PLACEHOLDER']), '/') }}",

        get approveUrl() {
            return this.currentId ? this.approveUrlTemplate.replace('PLACEHOLDER', this.currentId) : '';
        },
        get rejectUrl() {
            return this.currentId ? this.rejectUrlTemplate.replace('PLACEHOLDER', this.currentId) : '';
        },

        openApprove(id) {
            this.currentId = id;
            this.submitting = false;
            this.approveOpen = true;
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        openReject(id) {
            this.currentId = id;
            this.submitting = false;
            this.rejectOpen = true;
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection