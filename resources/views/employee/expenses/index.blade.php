@extends('layouts.employee')

@section('title', 'My Expenses')
@section('page-title', 'My Expenses')

@section('content')
<div x-data="expensesIndex()">

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

        $maxCatTotal = $byCategory->max('total') ?: 1;
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         HERO — Reimbursable total + apply CTA
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 grid lg:grid-cols-[1.5fr_1fr] gap-6 items-center">
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Reimbursements</p>
                <h1 class="text-white text-2xl lg:text-3xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $sym }}{{ number_format($totals->reimbursable_amount, 2) }}
                </h1>
                <p class="text-white/75 text-sm mt-1.5">
                    awaiting reimbursement &middot;
                    {{ (int) ($counters->s ?? 0) }} pending &middot;
                    {{ (int) ($counters->a ?? 0) }} approved
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('employee.expenses.create', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white text-gray-900 hover:bg-white/95 transition-all"
                       style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Submit Expense
                    </a>
                </div>
            </div>

            {{-- Year picker + summary --}}
            <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Viewing year</p>
                    <form method="GET" action="{{ route('employee.expenses.index', $tenantSlug) }}">
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
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Paid</p>
                        <p class="text-white text-lg font-black font-mono mt-0.5">
                            {{ $sym }}{{ number_format($totals->paid_amount, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Approved</p>
                        <p class="text-white text-lg font-black font-mono mt-0.5">
                            {{ $sym }}{{ number_format($totals->approved_amount, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Pending</p>
                        <p class="text-white/90 text-sm font-bold font-mono mt-0.5">
                            {{ $sym }}{{ number_format($totals->pending_amount, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Rejected</p>
                        <p class="text-white/90 text-sm font-bold font-mono mt-0.5">
                            {{ $sym }}{{ number_format($totals->rejected_amount, 0) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         CATEGORY BREAKDOWN
    ═══════════════════════════════════════════════════════════════ --}}
    @if($byCategory->isNotEmpty())
        <div class="lmt-card mb-6" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-slate-100 uppercase tracking-wider">Top categories</h3>
                    <p class="text-xs text-gray-800 mt-0.5">Approved & paid in {{ $year }}</p>
                </div>
                <i data-lucide="pie-chart" class="w-5 h-5 text-gray-800"></i>
            </div>
            <div class="space-y-3">
                @foreach($byCategory as $cb)
                    @php $pct = $maxCatTotal > 0 ? round(($cb->total / $maxCatTotal) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:var(--brand-500);"></span>
                                <span class="text-sm font-bold text-gray-700 dark:text-slate-200 truncate">{{ $cb->category->name ?? 'Uncategorised' }}</span>
                                <span class="text-[10px] text-gray-800 font-bold">&middot; {{ $cb->count }} {{ Str::plural('expense', $cb->count) }}</span>
                            </div>
                            <span class="font-mono font-bold text-sm text-gray-900 dark:text-slate-100 ml-2 flex-shrink-0">
                                {{ $sym }}{{ number_format($cb->total, 0) }}
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                 style="width: {{ $pct }}%; background:linear-gradient(90deg,var(--brand-500),var(--brand-600));"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         LIST + FILTERS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-5 border-b border-gray-100 dark:border-slate-700">
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">All Expenses</h2>
                <p class="text-xs text-gray-800 mt-0.5">
                    {{ (int) $counters->total }} expense{{ ($counters->total ?? 0) == 1 ? '' : 's' }} in {{ $year }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @php
                    $chips = [
                        ['key' => null,        'label' => 'All',       'count' => (int) ($counters->total ?? 0)],
                        ['key' => 'draft',     'label' => 'Drafts',    'count' => (int) ($counters->d ?? 0)],
                        ['key' => 'submitted', 'label' => 'Pending',   'count' => (int) ($counters->s ?? 0)],
                        ['key' => 'approved',  'label' => 'Approved',  'count' => (int) ($counters->a ?? 0)],
                        ['key' => 'paid',      'label' => 'Paid',      'count' => (int) ($counters->p ?? 0)],
                        ['key' => 'rejected',  'label' => 'Rejected',  'count' => (int) ($counters->r ?? 0)],
                    ];
                @endphp
                @foreach($chips as $chip)
                    @php $isActive = $status === $chip['key']; @endphp
                    <a href="{{ route('employee.expenses.index', array_filter([
                            'tenant' => $tenantSlug,
                            'year' => $year,
                            'category' => $catId,
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

                {{-- Category filter --}}
                <form method="GET" class="contents">
                    <input type="hidden" name="year"   value="{{ $year }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <select name="category" onchange="this.form.submit()"
                            class="lmt-input lmt-btn-sm" style="width:auto;min-width:140px;">
                        <option value="">All categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ (int) $catId === (int) $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Table --}}
        @if($expenses->isEmpty())
            <div class="text-center py-16 px-5">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                    <i data-lucide="wallet" class="w-7 h-7 text-gray-800"></i>
                </div>
                <p class="text-sm font-bold text-gray-700 dark:text-slate-200">No expenses found</p>
                <p class="text-xs text-gray-800 mt-1">Submit your first expense to get reimbursed.</p>
                <a href="{{ route('employee.expenses.create', $tenantSlug) }}" class="lmt-btn-primary lmt-btn-sm mt-4 inline-flex">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Submit Expense
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="lmt-table">
                    <thead>
                        <tr>
                            <th class="text-left">Expense #</th>
                            <th class="text-left">Title</th>
                            <th class="text-left hidden md:table-cell">Category</th>
                            <th class="text-left hidden lg:table-cell">Date</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Status</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $e)
                            @php
                                [$statusLbl, $statusCls] = match($e->status) {
                                    'draft'     => ['Draft',     'lmt-badge-gray'],
                                    'submitted' => ['Pending',   'lmt-badge-amber'],
                                    'approved'  => ['Approved',  'lmt-badge-green'],
                                    'rejected'  => ['Rejected',  'lmt-badge-red'],
                                    'paid'      => ['Paid',      'lmt-badge-green'],
                                    'cancelled' => ['Cancelled', 'lmt-badge-gray'],
                                    default     => [ucfirst($e->status), 'lmt-badge-gray'],
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50">
                                <td>
                                    <a href="{{ route('employee.expenses.show', [$tenantSlug, $e->id]) }}"
                                       class="font-mono text-xs font-bold hover:underline" style="color:var(--brand-600);">
                                        {{ $e->expense_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate" style="max-width:240px;">
                                            {{ $e->title }}
                                        </span>
                                        @if($e->receipt_path)
                                            <i data-lucide="paperclip" class="w-3.5 h-3.5 text-gray-800" title="Has receipt"></i>
                                        @endif
                                        @if($e->is_mileage)
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 uppercase">Mileage</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="text-xs text-gray-600 dark:text-slate-400">{{ $e->category?->name ?? '—' }}</span>
                                </td>
                                <td class="hidden lg:table-cell text-xs text-gray-800">
                                    {{ $e->expense_date?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    <span class="font-mono font-bold text-sm text-gray-900 dark:text-slate-100">
                                        {{ $sym }}{{ number_format($e->amount, 2) }}
                                    </span>
                                </td>
                                <td><span class="{{ $statusCls }}">{{ $statusLbl }}</span></td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('employee.expenses.show', [$tenantSlug, $e->id]) }}"
                                           class="text-xs font-bold transition-colors hover:underline px-2 py-1"
                                           style="color:var(--brand-500);">
                                            View
                                        </a>
                                        @if(in_array($e->status, ['draft', 'submitted']))
                                            <button @click="confirmDelete({{ $e->id }}, '{{ $e->expense_number }}')"
                                                    class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors px-2 py-1">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                {{ $expenses->links() }}
            </div>
        @endif
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
                <p class="text-sm text-gray-800 mt-2">
                    Expense <span class="font-mono font-bold" x-text="deleteLabel"></span> will be permanently deleted. This cannot be undone.
                </p>
            </div>
            <div class="flex justify-center gap-2 mt-6">
                <button @click="deleteOpen=false" class="lmt-btn-secondary">Keep it</button>
                <form :action="deleteUrl" method="POST">
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
function expensesIndex() {
    return {
        deleteOpen: false,
        deleteId: null,
        deleteLabel: '',
        deleteSubmitting: false,
        deleteUrlTemplate: "{{ rtrim(route('employee.expenses.destroy', [$tenantSlug, 'PLACEHOLDER']), '/') }}",

        get deleteUrl() {
            return this.deleteId ? this.deleteUrlTemplate.replace('PLACEHOLDER', this.deleteId) : '';
        },

        confirmDelete(id, label) {
            this.deleteId = id;
            this.deleteLabel = label;
            this.deleteOpen = true;
            this.deleteSubmitting = false;
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