@extends('layouts.employee')

@section('title', $member->full_name)
@section('page-title', 'Team Member')

@section('content')
<div class="max-w-6xl mx-auto">

    @php
        $cur = $tenantCurrency ?? 'USD';
        $sym = match($cur) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€', 'GBP' => '£', 'JPY', 'CNY' => '¥',
            'INR' => '₹', 'PKR' => 'Rs',
            default => $cur.' ',
        };

        [$statusLbl, $statusBg, $statusFg, $statusIcon] = match(true) {
            $isOnLeaveToday                         => ['On leave today', '#fffbeb', '#d97706', 'palmtree'],
            (bool) ($todayAttendance?->clock_in_at) => ['Working today',  '#ecfdf5', '#10b981', 'circle-dot'],
            default                                 => ['Away',           '#f3f4f6', '#6b7280', 'circle'],
        };
    @endphp

    {{-- Back nav --}}
    <a href="{{ route('employee.team.index', $tenantSlug) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-slate-200 transition-colors mb-5">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Back to team</span>
    </a>

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
         PROFILE HEADER
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card mb-5" data-lmt-anim="fade-up">
        <div class="flex flex-col sm:flex-row gap-5 items-start">
            <img src="{{ $member->avatar_url }}" alt="{{ $member->full_name }}"
                 class="w-20 h-20 rounded-2xl ring-2 ring-white dark:ring-slate-700 shadow object-cover flex-shrink-0"/>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                          style="background:{{ $statusBg }};color:{{ $statusFg }};">
                        <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                        {{ $statusLbl }}
                    </span>
                </div>
                <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $member->full_name }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $member->position?->title ?? 'No position' }}
                    @if($member->department) &middot; {{ $member->department->name }} @endif
                </p>

                <div class="flex flex-wrap gap-3 mt-3 text-xs text-gray-500">
                    @if($member->user?->email)
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                            <a href="mailto:{{ $member->user->email }}" class="hover:underline">{{ $member->user->email }}</a>
                        </span>
                    @endif
                    @if($member->phone)
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                            {{ $member->phone }}
                        </span>
                    @endif
                    @if($member->location)
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                            {{ $member->location->name ?? 'Unknown' }}
                        </span>
                    @endif
                    @if($member->hire_date)
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            Joined {{ $member->hire_date->format('M j, Y') }} ({{ $member->service_months }} mo)
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @php
            $statCards = [
                ['Pending leaves',   $pendingLeaves,   'calendar',   $pendingLeaves > 0 ? 'amber' : 'gray'],
                ['Pending expenses', $pendingExpenses, 'receipt',    $pendingExpenses > 0 ? 'amber' : 'gray'],
                ['Open tasks',       $openTasks->count(), 'check-square', 'brand'],
                ['Leave balance',    $leaveBalances->sum(fn($b) => (float) ($b->allocated + $b->accrued + $b->carried_over + $b->adjusted - $b->used - $b->pending)), 'wallet', 'green'],
            ];
            $colorClasses = [
                'gray'  => ['bg' => 'bg-gray-100 dark:bg-slate-700', 'fg' => 'text-gray-500'],
                'amber' => ['bg' => 'bg-amber-50 dark:bg-amber-500/15', 'fg' => 'text-amber-600'],
                'green' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/15', 'fg' => 'text-emerald-600'],
                'brand' => ['bg' => '', 'fg' => '', 'inline' => 'background:var(--brand-50);color:var(--brand-600);'],
            ];
        @endphp
        @foreach($statCards as [$lbl, $val, $ico, $color])
            @php $c = $colorClasses[$color]; @endphp
            <div class="lmt-card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">{{ $lbl }}</p>
                        <p class="text-2xl font-black font-mono mt-1 text-gray-900 dark:text-slate-100">
                            @if(is_float($val) && $val !== (float) (int) $val)
                                {{ number_format($val, 1) }}
                            @else
                                {{ (int) $val }}
                            @endif
                        </p>
                    </div>
                    <div class="w-9 h-9 rounded-xl {{ $c['bg'] ?? '' }} {{ $c['fg'] ?? '' }} flex items-center justify-center"
                         @if(! empty($c['inline'])) style="{{ $c['inline'] }}" @endif>
                        <i data-lucide="{{ $ico }}" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Recent leaves --}}
        <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
            <div class="p-5 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Recent leaves</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Last 10 requests</p>
                </div>
            </div>
            @if($leaves->isEmpty())
                <div class="text-center py-12 px-5">
                    <i data-lucide="calendar-off" class="w-6 h-6 text-gray-300 mx-auto mb-2"></i>
                    <p class="text-xs text-gray-500">No leave requests yet.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($leaves as $l)
                        @php
                            [$lLbl, $lCls] = match($l->status) {
                                'pending'  => ['Pending',  'lmt-badge-amber'],
                                'approved' => ['Approved', 'lmt-badge-green'],
                                'rejected' => ['Rejected', 'lmt-badge-red'],
                                'cancelled'=> ['Cancelled','lmt-badge-gray'],
                                default    => [ucfirst($l->status), 'lmt-badge-gray'],
                            };
                        @endphp
                        <div class="px-5 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">
                                    {{ $l->leaveType?->name ?? 'Leave' }}
                                    <span class="font-mono text-[10px] text-gray-400 ml-1">{{ $l->request_number }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $l->start_date->format('M j') }} – {{ $l->end_date->format('M j') }}
                                    &middot; {{ (float) $l->total_days }} day{{ (float) $l->total_days === 1.0 ? '' : 's' }}
                                </p>
                            </div>
                            <span class="{{ $lCls }} flex-shrink-0">{{ $lLbl }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            @if($pendingLeaves > 0)
                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                    <a href="{{ route('employee.team.approvals.leaves', $tenantSlug) }}"
                       class="text-xs font-bold hover:underline" style="color:var(--brand-500);">
                        Review {{ $pendingLeaves }} pending
                    </a>
                </div>
            @endif
        </div>

        {{-- Recent expenses --}}
        <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
            <div class="p-5 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Recent expenses</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Last 10 submissions</p>
                </div>
            </div>
            @if($expenses->isEmpty())
                <div class="text-center py-12 px-5">
                    <i data-lucide="wallet" class="w-6 h-6 text-gray-300 mx-auto mb-2"></i>
                    <p class="text-xs text-gray-500">No expenses submitted yet.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($expenses as $e)
                        @php
                            [$eLbl, $eCls] = match($e->status) {
                                'submitted' => ['Pending',  'lmt-badge-amber'],
                                'approved'  => ['Approved', 'lmt-badge-green'],
                                'paid'      => ['Paid',     'lmt-badge-green'],
                                'rejected'  => ['Rejected', 'lmt-badge-red'],
                                default     => [ucfirst($e->status), 'lmt-badge-gray'],
                            };
                        @endphp
                        <div class="px-5 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">
                                    {{ $e->title }}
                                    <span class="font-mono text-[10px] text-gray-400 ml-1">{{ $e->expense_number }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $sym }}{{ number_format($e->amount, 2) }}
                                    &middot; {{ $e->category?->name ?? 'Uncategorised' }}
                                </p>
                            </div>
                            <span class="{{ $eCls }} flex-shrink-0">{{ $eLbl }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            @if($pendingExpenses > 0)
                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                    <a href="{{ route('employee.team.approvals.expenses', $tenantSlug) }}"
                       class="text-xs font-bold hover:underline" style="color:var(--brand-500);">
                        Review {{ $pendingExpenses }} pending
                    </a>
                </div>
            @endif
        </div>

        {{-- Open tasks --}}
        <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
            <div class="p-5 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Open tasks</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $openTasks->count() }} assigned</p>
            </div>
            @if($openTasks->isEmpty())
                <div class="text-center py-12 px-5">
                    <i data-lucide="check-circle" class="w-6 h-6 text-emerald-300 mx-auto mb-2"></i>
                    <p class="text-xs text-gray-500">No open tasks. All clear.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($openTasks as $t)
                        @php
                            [$tLbl, $tCls] = match($t->status) {
                                'todo'        => ['To do',       'lmt-badge-gray'],
                                'in_progress' => ['In progress', 'lmt-badge-amber'],
                                'in_review'   => ['In review',   'lmt-badge-amber'],
                                'on_hold'     => ['On hold',     'lmt-badge-gray'],
                                default       => [ucfirst($t->status), 'lmt-badge-gray'],
                            };
                            $tIsOverdue = $t->due_date && $t->due_date->isPast();
                        @endphp
                        <div class="px-5 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">
                                    {{ $t->title }}
                                    <span class="font-mono text-[10px] text-gray-400 ml-1">{{ $t->task_code }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $t->project?->name ?? 'No project' }}
                                    @if($t->due_date)
                                        &middot;
                                        <span class="{{ $tIsOverdue ? 'text-red-600 font-bold' : '' }}">
                                            Due {{ $t->due_date->format('M j') }}
                                        </span>
                                    @endif
                                </p>
                            </div>
                            <span class="{{ $tCls }} flex-shrink-0">{{ $tLbl }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Leave balances --}}
        <div class="lmt-card" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Leave balances</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Year {{ now()->year }}</p>
                </div>
            </div>
            @if($leaveBalances->isEmpty())
                <p class="text-xs text-gray-400">No leave balances configured.</p>
            @else
                <div class="space-y-3">
                    @foreach($leaveBalances as $bal)
                        @php
                            $available = (float) ($bal->allocated + $bal->accrued + $bal->carried_over + $bal->adjusted - $bal->used - $bal->pending);
                            $totalAllocated = (float) ($bal->allocated + $bal->accrued + $bal->carried_over + $bal->adjusted);
                            $usedPct = $totalAllocated > 0 ? round((float) $bal->used / $totalAllocated * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-gray-700 dark:text-slate-300">{{ $bal->leaveType?->name ?? 'Unknown' }}</span>
                                <span class="text-xs">
                                    <span class="font-mono font-bold text-gray-900 dark:text-slate-100">{{ number_format($available, 1) }}</span>
                                    <span class="text-gray-400">/ {{ number_format($totalAllocated, 0) }} day{{ $totalAllocated === 1.0 ? '' : 's' }}</span>
                                </span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     style="width:{{ $usedPct }}%; background:linear-gradient(90deg,var(--brand-500),var(--brand-600));"></div>
                            </div>
                            @if((float) $bal->pending > 0)
                                <p class="text-[10px] text-amber-600 mt-1">{{ number_format((float) $bal->pending, 1) }} day(s) pending</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Recent attendance --}}
        <div class="lmt-card lg:col-span-2" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-slate-100">Last 14 days</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Attendance summary</p>
                </div>
            </div>
            @if($recentAttendance->isEmpty())
                <p class="text-xs text-gray-400">No attendance records in the last 14 days.</p>
            @else
                @php
                    $totalDays = $recentAttendance->count();
                    $present   = $recentAttendance->where('status', 'present')->count();
                    $late      = $recentAttendance->where('is_late', true)->count();
                    $totalHrs  = $recentAttendance->sum('total_hours');
                @endphp

                <div class="grid grid-cols-4 gap-3 mb-4">
                    <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Days logged</p>
                        <p class="text-lg font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $totalDays }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Present</p>
                        <p class="text-lg font-black font-mono mt-1 text-emerald-600">{{ $present }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Late</p>
                        <p class="text-lg font-black font-mono mt-1 {{ $late > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $late }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Total hours</p>
                        <p class="text-lg font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ number_format((float) $totalHrs, 1) }}</p>
                    </div>
                </div>

                {{-- Day strip --}}
                <div class="flex items-center gap-1 overflow-x-auto pb-2">
                    @for($i = 13; $i >= 0; $i--)
                        @php
                            $day = now()->subDays($i);
                            $row = $recentAttendance->firstWhere('work_date', $day->copy()->startOfDay());

                            [$dayBg, $dayFg] = match(true) {
                                ! $row                       => ['bg-gray-100 dark:bg-slate-800', 'text-gray-400'],
                                $row->status === 'on_leave'  => ['bg-amber-100', 'text-amber-700'],
                                $row->status === 'holiday'   => ['bg-blue-100',  'text-blue-700'],
                                $row->status === 'weekend'   => ['bg-gray-100',  'text-gray-400'],
                                $row->status === 'absent'    => ['bg-red-100',   'text-red-700'],
                                $row->is_late                => ['bg-amber-100', 'text-amber-700'],
                                default                      => ['bg-emerald-100','text-emerald-700'],
                            };
                        @endphp
                        <div class="flex-shrink-0 w-12 text-center">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">{{ $day->format('D') }}</p>
                            <div class="mt-1 rounded-lg {{ $dayBg }} {{ $dayFg }} p-2"
                                 title="{{ $day->format('M j') }}{{ $row ? ' · ' . ucfirst(str_replace('_',' ',$row->status)) : '' }}">
                                <p class="font-mono font-black text-sm">{{ $day->format('j') }}</p>
                            </div>
                        </div>
                    @endfor
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