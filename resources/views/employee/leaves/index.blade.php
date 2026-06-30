@extends('layouts.employee')

@section('title', 'My Leaves')
@section('page-title', 'My Leaves')

@push('head')
<style>
    .drawer-backdrop { background:rgba(15,23,42,.45); backdrop-filter:blur(4px); }
    .drawer-panel    { max-width:520px; }
</style>
@endpush

@section('content')
<div x-data="leavesIndex()">

    {{-- ═══════════════════════════════════════════════════════════════
         HERO — Summary cards + Apply CTA
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 60%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 grid lg:grid-cols-[1.5fr_1fr] gap-6 items-center">
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Time off</p>
                <h1 class="text-white text-2xl lg:text-3xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $year }} leave balance
                </h1>
                <p class="text-white/75 text-sm mt-1.5">
                    {{ $summary->available }} day{{ $summary->available == 1 ? '' : 's' }} available · {{ $summary->used }} used · {{ $summary->pending }} pending
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('employee.leaves.create', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white text-gray-900 hover:bg-white/95 transition-all"
                       style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Apply for Leave
                    </a>
                </div>
            </div>

            {{-- Year switcher --}}
            <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-5">
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider mb-2">Viewing year</p>
                <form method="GET" action="{{ route('employee.leaves.index', $tenantSlug) }}">
                    <select name="year" onchange="this.form.submit()"
                            class="w-full bg-white/15 border border-white/25 rounded-xl px-3 py-2.5 text-white font-bold text-base appearance-none cursor-pointer"
                            style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 16 16&quot; fill=&quot;white&quot;><path d=&quot;M4 6l4 4 4-4&quot;/></svg>'); background-repeat:no-repeat; background-position:right 12px center;">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ (int) $y === (int) $year ? 'selected' : '' }}
                                    style="color:#1f2937;">{{ $y }}</option>
                        @endforeach
                    </select>
                </form>

                {{-- Mini total bar --}}
                <div class="mt-4">
                    @php
                        $totalAll = max(1, $summary->total);
                        $usedPct  = round(($summary->used / $totalAll) * 100);
                        $pendPct  = round(($summary->pending / $totalAll) * 100);
                    @endphp
                    <div class="flex h-2 rounded-full overflow-hidden bg-white/20">
                        <div class="h-full bg-white" style="width: {{ $usedPct }}%"></div>
                        <div class="h-full bg-amber-300" style="width: {{ $pendPct }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-white/70 font-bold mt-1.5 uppercase tracking-wide">
                        <span>Used {{ $summary->used }}</span>
                        <span>Pending {{ $summary->pending }}</span>
                        <span>Total {{ $summary->total }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         BALANCES BY TYPE
    ═══════════════════════════════════════════════════════════════ --}}
    @if($balances->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @foreach($balances as $b)
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider truncate">{{ $b->name }}</p>
                                @if(! $b->is_paid)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 uppercase">Unpaid</span>
                                @endif
                            </div>
                            <p class="text-3xl font-black text-gray-900 dark:text-slate-100 mt-1 font-mono">
                                {{ $b->available }}
                                <span class="text-sm text-gray-400 font-bold">/ {{ $b->total }}</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">days available</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:{{ $b->color }}1a; color:{{ $b->color }};">
                            <i data-lucide="calendar-off" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             style="width: {{ $b->used_pct }}%; background:{{ $b->color }};"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-gray-500 font-semibold mt-2">
                        <span>{{ $b->used }} used</span>
                        @if($b->pending > 0)
                            <span class="text-amber-600">{{ $b->pending }} pending</span>
                        @else
                            <span>{{ $b->used_pct }}%</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         FILTERS + HISTORY TABLE
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">

        {{-- Filter bar --}}
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
                    Leave History
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $requests->total() }} request{{ $requests->total() === 1 ? '' : 's' }} in {{ $year }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Status filter chips --}}
                @php
                    $chips = [
                        ['key' => null,        'label' => 'All',        'count' => $requests->total()],
                        ['key' => 'pending',   'label' => 'Pending',    'count' => (int) ($counters->p ?? 0)],
                        ['key' => 'approved',  'label' => 'Approved',   'count' => (int) ($counters->a ?? 0)],
                        ['key' => 'rejected',  'label' => 'Rejected',   'count' => (int) ($counters->r ?? 0)],
                        ['key' => 'cancelled', 'label' => 'Cancelled',  'count' => (int) ($counters->c ?? 0)],
                    ];
                @endphp
                @foreach($chips as $chip)
                    @php $isActive = $status === $chip['key']; @endphp
                    <a href="{{ route('employee.leaves.index', array_filter([
                            'tenant' => $tenantSlug,
                            'year' => $year,
                            'type' => $typeId,
                            'status' => $chip['key'],
                       ])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all
                              {{ $isActive
                                    ? 'border-transparent text-white'
                                    : 'border-gray-200 dark:border-slate-700 text-gray-500 hover:border-gray-300' }}"
                       @if($isActive) style="background:var(--brand-500);" @endif>
                        {{ $chip['label'] }}
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $isActive ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">
                            {{ $chip['count'] }}
                        </span>
                    </a>
                @endforeach

                {{-- Type filter --}}
                <form method="GET" class="contents">
                    <input type="hidden" name="year"   value="{{ $year }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <select name="type" onchange="this.form.submit()"
                            class="lmt-input lmt-btn-sm" style="width:auto;min-width:140px;">
                        <option value="">All types</option>
                        @foreach($allTypes as $t)
                            <option value="{{ $t->id }}" {{ (int) $typeId === (int) $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Table --}}
        @if($requests->isEmpty())
            <div class="text-center py-16">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                    <i data-lucide="calendar-off" class="w-7 h-7 text-gray-300"></i>
                </div>
                <p class="text-sm text-gray-500">No leave requests found.</p>
                <a href="{{ route('employee.leaves.create', $tenantSlug) }}" class="lmt-btn-primary lmt-btn-sm mt-4 inline-flex">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Apply Now
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="lmt-table">
                    <thead>
                        <tr>
                            <th class="text-left">Request #</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Dates</th>
                            <th class="text-left">Days</th>
                            <th class="text-left">Status</th>
                            <th class="text-left hidden md:table-cell">Applied</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $r)
                            @php
                                [$statusLbl, $statusCls] = match($r->status) {
                                    'approved'  => ['Approved',  'lmt-badge-green'],
                                    'pending'   => ['Pending',   'lmt-badge-amber'],
                                    'rejected'  => ['Rejected',  'lmt-badge-red'],
                                    'cancelled' => ['Cancelled', 'lmt-badge-gray'],
                                    default     => [ucfirst($r->status), 'lmt-badge-gray'],
                                };
                                $start = \Carbon\Carbon::parse($r->start_date);
                                $end   = \Carbon\Carbon::parse($r->end_date);
                                $color = $r->leaveType?->color ?? '#6C7DF7';
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50">
                                <td>
                                    <span class="font-mono text-xs font-bold text-gray-700 dark:text-slate-200">
                                        {{ $r->request_number }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $color }};"></span>
                                        <span class="font-bold text-sm text-gray-900 dark:text-slate-100">{{ $r->leaveType?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900 dark:text-slate-100">
                                        {{ $start->format('M j') }}
                                        @if($start->ne($end))
                                            <span class="text-gray-400">→</span> {{ $end->format('M j') }}
                                        @endif
                                    </div>
                                    @if($r->day_part && $r->day_part !== 'full')
                                        <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide mt-0.5">
                                            {{ str_replace('_', ' ', $r->day_part) }}
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-mono font-bold text-sm">{{ rtrim(rtrim(number_format($r->total_days, 1), '0'), '.') }}</span>
                                </td>
                                <td><span class="{{ $statusCls }}">{{ $statusLbl }}</span></td>
                                <td class="hidden md:table-cell text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($r->created_at)->format('M j, Y') }}
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openDetail({{ $r->id }})"
                                                class="text-xs font-bold transition-colors hover:underline px-2 py-1"
                                                style="color:var(--brand-500);">
                                            View
                                        </button>
                                        @if(in_array($r->status, ['pending']) || ($r->status === 'approved' && $start->isFuture()))
                                            <button @click="confirmCancel({{ $r->id }}, '{{ $r->request_number }}')"
                                                    class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors px-2 py-1">
                                                Cancel
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
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         DETAIL DRAWER
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="drawerOpen" x-cloak
         class="fixed inset-0 z-50 flex justify-end"
         x-transition.opacity>
        <div class="absolute inset-0 drawer-backdrop" @click="closeDrawer()"></div>
        <div class="relative drawer-panel w-full bg-white dark:bg-slate-900 shadow-2xl h-full overflow-y-auto"
             x-show="drawerOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">

            <button @click="closeDrawer()"
                    class="absolute top-3 right-3 w-9 h-9 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center transition-colors z-10">
                <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
            </button>

            <div x-show="drawerLoading" class="flex items-center justify-center py-20 text-sm text-gray-400 gap-2">
                <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Loading…
            </div>
            <div x-show="!drawerLoading" x-html="drawerHtml"></div>
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
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Cancel this leave?</h3>
                <p class="text-sm text-gray-500 mt-2">
                    Request <span class="font-mono font-bold" x-text="cancelLabel"></span> will be cancelled and your balance restored. This cannot be undone.
                </p>
            </div>
            <div class="flex justify-center gap-2 mt-6">
                <button @click="cancelOpen=false" class="lmt-btn-secondary">Keep it</button>
                <button @click="doCancel()" class="lmt-btn-danger" :disabled="cancelSubmitting">
                    <span x-show="!cancelSubmitting"><i data-lucide="trash-2" class="w-4 h-4"></i> Cancel Leave</span>
                    <span x-show="cancelSubmitting">Cancelling…</span>
                </button>
            </div>
        </div>
    </div>

</div>

@php $leavesShowBaseUrl = rtrim(route('employee.leaves.show', [$tenantSlug, 'PLACEHOLDER']), '/'); @endphp
@push('scripts')
<script>
function leavesIndex() {
    return {
        drawerOpen: false,
        drawerLoading: false,
        drawerHtml: '',

        cancelOpen: false,
        cancelId: null,
        cancelLabel: '',
        cancelSubmitting: false,

        async openDetail(id) {
            this.drawerOpen = true;
            this.drawerLoading = true;
            this.drawerHtml = '';
            try {
                const url = "{{ rtrim(route('employee.leaves.show', [$tenantSlug, 'PLACEHOLDER']), '/') }}".replace('PLACEHOLDER', id);
                const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const d = await r.json();
                this.drawerHtml = d.html || '<p class="p-6 text-sm text-gray-500">Could not load.</p>';
            } catch (e) {
                this.drawerHtml = '<p class="p-6 text-sm text-red-500">Failed to load details.</p>';
            } finally {
                this.drawerLoading = false;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            }
        },
        closeDrawer() { this.drawerOpen = false; this.drawerHtml = ''; },

        confirmCancel(id, label) {
            this.cancelId = id;
            this.cancelLabel = label;
            this.cancelOpen = true;
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        async doCancel() {
            if (this.cancelSubmitting) return;
            this.cancelSubmitting = true;
            try {
                const url = "{{ rtrim(route('employee.leaves.cancel', [$tenantSlug, 'PLACEHOLDER']), '/') }}".replace('PLACEHOLDER', this.cancelId);
                const r = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const d = await r.json().catch(() => ({}));
                if (r.ok && d.success) {
                    window.lmtToast && lmtToast(d.message || 'Cancelled.', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    window.lmtToast && lmtToast(d.message || 'Could not cancel.', 'error');
                }
            } catch (e) {
                window.lmtToast && lmtToast('Network error.', 'error');
            } finally {
                this.cancelSubmitting = false;
                this.cancelOpen = false;
            }
        },
    };
}
</script>
@endpush

@endsection