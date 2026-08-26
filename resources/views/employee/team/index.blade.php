@extends('layouts.employee')

@section('title', 'My Team')
@section('page-title', 'My Team')

@section('content')
<div>
    @php
        $cur = $tenantCurrency ?? 'USD';
        $sym = match($cur) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€', 'GBP' => '£', 'JPY', 'CNY' => '¥',
            'INR' => '₹', 'PKR' => 'Rs',
            default => $cur.' ',
        };

        $hasPending = ($pendingLeaves + $pendingExpenses + $pendingCorrections) > 0;
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         HERO — team summary + pending approvals
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 grid lg:grid-cols-[1.4fr_1fr] gap-6 items-center">
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">My team</p>
                <h1 class="text-white text-2xl lg:text-3xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $headcount }} direct report{{ $headcount === 1 ? '' : 's' }}
                </h1>
                <p class="text-white/75 text-sm mt-1.5">
                    {{ $clockedIn }} clocked in &middot;
                    {{ $onLeaveCount }} on leave today
                    @if($hasPending)
                        &middot; <span class="text-amber-100">{{ $pendingLeaves + $pendingExpenses + $pendingCorrections }} pending approval{{ ($pendingLeaves + $pendingExpenses + $pendingCorrections) === 1 ? '' : 's' }}</span>
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('employee.team.approvals.leaves', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white text-gray-900 hover:bg-white/95 transition-all"
                       style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                        <i data-lucide="calendar-check" class="w-4 h-4"></i>
                        Leave approvals
                        @if($pendingLeaves > 0)
                            <span class="bg-red-500 text-white text-[10px] font-black rounded-full w-5 h-5 inline-flex items-center justify-center">{{ $pendingLeaves }}</span>
                        @endif
                    </a>
                    <a href="{{ route('employee.team.approvals.expenses', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white/15 border border-white/25 text-white hover:bg-white/25 transition-all">
                        <i data-lucide="receipt" class="w-4 h-4"></i>
                        Expense approvals
                        @if($pendingExpenses > 0)
                            <span class="bg-amber-400 text-amber-900 text-[10px] font-black rounded-full w-5 h-5 inline-flex items-center justify-center">{{ $pendingExpenses }}</span>
                        @endif
                    </a>
                    <a href="{{ route('employee.team.approvals.corrections', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white/15 border border-white/25 text-white hover:bg-white/25 transition-all">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        Correction approvals
                        @if($pendingCorrections > 0)
                            <span class="bg-amber-400 text-amber-900 text-[10px] font-black rounded-full w-5 h-5 inline-flex items-center justify-center">{{ $pendingCorrections }}</span>
                        @endif
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Headcount</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $headcount }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Working</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $clockedIn }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">On leave</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $onLeaveCount }}</p>
                </div>
            </div>
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

    <div class="grid lg:grid-cols-[1.4fr_1fr] gap-5">

        {{-- ═════ LEFT: team member grid ═════ --}}
        <div class="space-y-5">
            <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
                <div class="p-5 border-b border-gray-100 dark:border-slate-700">
                    <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">Team members</h2>
                    <p class="text-xs text-gray-800 mt-0.5">Click on someone to see their full profile</p>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($reports as $r)
                        @php
                            [$statusLbl, $statusBg, $statusFg, $statusIcon] = match($r->_status_today) {
                                'clocked_in' => ['Working',  '#ecfdf5', '#10b981', 'circle-dot'],
                                'on_leave'   => ['On leave', '#fffbeb', '#d97706', 'palmtree'],
                                default      => ['Away',     '#f3f4f6', '#6b7280', 'circle'],
                            };
                            $pendingItems = $r->_pending_leaves + $r->_pending_expenses;
                        @endphp
                        <a href="{{ route('employee.team.show', [$tenantSlug, $r->id]) }}"
                           class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50/70 dark:hover:bg-slate-800/50 transition-colors">

                            <div class="relative flex-shrink-0">
                                <img src="{{ $r->avatar_url }}" alt="{{ $r->full_name }}"
                                     class="w-11 h-11 rounded-full ring-2 ring-white dark:ring-slate-800 object-cover"/>
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full ring-2 ring-white dark:ring-slate-800"
                                      style="background:{{ $statusFg }};" title="{{ $statusLbl }}"></span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $r->full_name }}</p>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                                          style="background:{{ $statusBg }};color:{{ $statusFg }};">
                                        <i data-lucide="{{ $statusIcon }}" class="w-2.5 h-2.5"></i>
                                        {{ $statusLbl }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-800 truncate">
                                    {{ $r->position?->title ?? 'No position' }}
                                    @if($r->department) &middot; {{ $r->department->name }} @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                @if($r->_pending_leaves > 0)
                                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300" title="Pending leaves">
                                        <i data-lucide="calendar" class="w-2.5 h-2.5"></i>
                                        {{ $r->_pending_leaves }}
                                    </span>
                                @endif
                                @if($r->_pending_expenses > 0)
                                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300" title="Pending expenses">
                                        <i data-lucide="receipt" class="w-2.5 h-2.5"></i>
                                        {{ $r->_pending_expenses }}
                                    </span>
                                @endif
                                @if($r->_open_tasks > 0)
                                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-50 text-gray-800 dark:bg-slate-700 dark:text-slate-300" title="Open tasks">
                                        <i data-lucide="check-square" class="w-2.5 h-2.5"></i>
                                        {{ $r->_open_tasks }}
                                    </span>
                                @endif
                                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-800 ml-1"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═════ RIGHT: recent activity ═════ --}}
        <div class="space-y-5">

            {{-- Recent leave requests --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-black text-gray-900 dark:text-slate-100 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-800"></i> Recent leaves
                    </h3>
                    <a href="{{ route('employee.team.approvals.leaves', ['tenant' => $tenantSlug, 'status' => 'all']) }}"
                       class="text-[10px] font-bold hover:underline" style="color:var(--brand-500);">View all</a>
                </div>

                @if($recentLeaves->isEmpty())
                    <p class="text-xs text-gray-800">No recent leave requests.</p>
                @else
                    <div class="space-y-2">
                        @foreach($recentLeaves as $l)
                            @php
                                [$lLbl, $lCls] = match($l->status) {
                                    'pending'  => ['Pending',  'lmt-badge-amber'],
                                    'approved' => ['Approved', 'lmt-badge-green'],
                                    'rejected' => ['Rejected', 'lmt-badge-red'],
                                    'cancelled'=> ['Cancelled','lmt-badge-gray'],
                                    default    => [ucfirst($l->status), 'lmt-badge-gray'],
                                };
                            @endphp
                            <div class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                @if($l->employee)
                                    <img src="{{ $l->employee->avatar_url }}" alt="{{ $l->employee->full_name }}"
                                         class="w-7 h-7 rounded-full object-cover flex-shrink-0"/>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-slate-100 truncate">{{ $l->employee?->full_name }}</p>
                                    <p class="text-[10px] text-gray-800 truncate">
                                        {{ $l->leaveType?->name ?? 'Leave' }} &middot; {{ (float) $l->total_days }} day{{ (float) $l->total_days === 1.0 ? '' : 's' }}
                                    </p>
                                </div>
                                <span class="{{ $lCls }} flex-shrink-0">{{ $lLbl }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent expenses --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-black text-gray-900 dark:text-slate-100 flex items-center gap-1.5">
                        <i data-lucide="receipt" class="w-4 h-4 text-gray-800"></i> Recent expenses
                    </h3>
                    <a href="{{ route('employee.team.approvals.expenses', ['tenant' => $tenantSlug, 'status' => 'all']) }}"
                       class="text-[10px] font-bold hover:underline" style="color:var(--brand-500);">View all</a>
                </div>

                @if($recentExpenses->isEmpty())
                    <p class="text-xs text-gray-800">No recent expense submissions.</p>
                @else
                    <div class="space-y-2">
                        @foreach($recentExpenses as $e)
                            @php
                                [$eLbl, $eCls] = match($e->status) {
                                    'submitted' => ['Pending',  'lmt-badge-amber'],
                                    'approved'  => ['Approved', 'lmt-badge-green'],
                                    'paid'      => ['Paid',     'lmt-badge-green'],
                                    'rejected'  => ['Rejected', 'lmt-badge-red'],
                                    default     => [ucfirst($e->status), 'lmt-badge-gray'],
                                };
                            @endphp
                            <div class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                @if($e->employee)
                                    <img src="{{ $e->employee->avatar_url }}" alt="{{ $e->employee->full_name }}"
                                         class="w-7 h-7 rounded-full object-cover flex-shrink-0"/>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-slate-100 truncate">{{ $e->employee?->full_name }}</p>
                                    <p class="text-[10px] text-gray-800 truncate">
                                        {{ $sym }}{{ number_format($e->amount, 2) }} &middot; {{ $e->category?->name ?? 'Uncategorised' }}
                                    </p>
                                </div>
                                <span class="{{ $eCls }} flex-shrink-0">{{ $eLbl }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recent attendance corrections --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-black text-gray-900 dark:text-slate-100 flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-4 h-4 text-gray-800"></i> Recent corrections
                    </h3>
                    <a href="{{ route('employee.team.approvals.corrections', ['tenant' => $tenantSlug, 'status' => 'all']) }}"
                       class="text-[10px] font-bold hover:underline" style="color:var(--brand-500);">View all</a>
                </div>

                @if($recentCorrections->isEmpty())
                    <p class="text-xs text-gray-800">No recent correction requests.</p>
                @else
                    <div class="space-y-2">
                        @foreach($recentCorrections as $c)
                            @php
                                [$cLbl, $cCls] = match($c->status) {
                                    'pending'   => ['Pending',   'lmt-badge-amber'],
                                    'approved'  => ['Approved',  'lmt-badge-green'],
                                    'rejected'  => ['Rejected',  'lmt-badge-red'],
                                    'cancelled' => ['Cancelled', 'lmt-badge-gray'],
                                    default     => [ucfirst($c->status), 'lmt-badge-gray'],
                                };
                            @endphp
                            <div class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                @if($c->employee)
                                    <img src="{{ $c->employee->avatar_url }}" alt="{{ $c->employee->full_name }}"
                                         class="w-7 h-7 rounded-full object-cover flex-shrink-0"/>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-slate-100 truncate">{{ $c->employee?->full_name }}</p>
                                    <p class="text-[10px] text-gray-800 truncate">
                                        {{ \Carbon\Carbon::parse($c->work_date)->format('M j, Y') }}
                                    </p>
                                </div>
                                <span class="{{ $cCls }} flex-shrink-0">{{ $cLbl }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
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