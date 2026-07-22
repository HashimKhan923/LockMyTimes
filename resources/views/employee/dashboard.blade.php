@extends('layouts.employee')

@section('title', 'Home')
@section('page-title', 'Home')

@php use Illuminate\Support\Number; @endphp

@section('content')

@if($noEmployee ?? false)
    {{-- Safety fallback if user has no employee record --}}
    <div class="lmt-card text-center py-12 max-w-xl mx-auto">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
            <i data-lucide="alert-triangle" class="w-8 h-8"></i>
        </div>
        <h2 class="text-xl font-black text-gray-900 mb-2">No employee profile linked</h2>
        <p class="text-gray-500 text-sm">Please contact your HR administrator to link your user account to an employee record.</p>
    </div>
@else

@php
    $hour      = now()->hour;
    $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = $emp->display_name ?? explode(' ', auth()->user()->name)[0];

    $statusMeta = match($clockStatus) {
        'not_clocked_in' => ['label'=>'Not clocked in', 'dot'=>'#94a3b8', 'pill'=>'bg-gray-100 text-gray-700 border-gray-200'],
        'clocked_in'     => ['label'=>'Clocked in',     'dot'=>'#10b981', 'pill'=>'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'on_break'       => ['label'=>'On break',       'dot'=>'#f59e0b', 'pill'=>'bg-amber-50 text-amber-700 border-amber-200'],
        'clocked_out'    => ['label'=>'Day complete',   'dot'=>'#6366f1', 'pill'=>'bg-indigo-50 text-indigo-700 border-indigo-200'],
    };

    // Shift progress (0–100)
    $shiftProgress = null;
    if ($shift) {
        $totalShiftMins = $shift->start_at->diffInMinutes($shift->end_at);
        $elapsedMins    = $shift->start_at->isPast()
            ? min($totalShiftMins, $shift->start_at->diffInMinutes(now()))
            : 0;
        $shiftProgress  = $totalShiftMins > 0 ? round(($elapsedMins / $totalShiftMins) * 100) : 0;
    }

    $hh = floor($liveWorkedMinutes / 60);
    $mm = $liveWorkedMinutes % 60;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════
     HERO — Greeting + Live Clock + Clock-in CTA
═══════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
     style="background:linear-gradient(135deg,var(--brand-500) 0%,var(--brand-600) 60%,#7C3AED 100%);"
     data-lmt-anim="fade-up">

    {{-- Soft background blobs --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5"></div>
        <div class="absolute top-4 right-1/4 w-32 h-32 rounded-full bg-white/[.03]"></div>
    </div>

    <div class="relative z-10 grid lg:grid-cols-[1.5fr_1fr] gap-6 items-center">

        {{-- LEFT — greeting + status + shift --}}
        <div>
            <div class="flex items-center gap-2 text-white/70 text-xs font-semibold mb-1">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                {{ now()->format('l, F j, Y') }}
            </div>
            <h1 class="text-white text-2xl lg:text-3xl font-black leading-tight">
                {{ $greeting }}, {{ $firstName }}
            </h1>
            <p class="text-white/70 text-sm mt-1.5 italic">"{{ $quote }}"</p>

            <div class="flex items-center gap-2 flex-wrap mt-4">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border bg-white/10 border-white/20 text-white">
                    <span class="w-2 h-2 rounded-full animate-pulse" style="background:{{ $statusMeta['dot'] }};"></span>
                    {{ $statusMeta['label'] }}
                </span>
                @if($shift)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-white/10 border border-white/20 text-white">
                        <i data-lucide="moon" class="w-3 h-3"></i>
                        {{ $shift->name }} · {{ $shift->label }}
                    </span>
                @endif
                @if($emp->location)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-white/10 border border-white/20 text-white">
                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                        {{ $emp->location->name }}
                    </span>
                @endif
            </div>
        </div>

        {{-- RIGHT — Live Clock + Action CTA --}}
        <div class="relative bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-5"
             x-data="liveClock()" x-init="start()">
            <div class="flex items-end gap-1">
                <span class="text-white text-4xl lg:text-5xl font-black tracking-tight font-mono" x-text="clock"></span>
                <span class="text-white/70 text-xs font-bold mb-2" x-text="ampm"></span>
            </div>
            <p class="text-white/60 text-xs font-medium mt-1" x-text="zone"></p>

            @if($clockStatus !== 'not_clocked_in')
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="bg-white/10 rounded-xl px-3 py-2">
                        <p class="text-white/60 text-[10px] font-bold uppercase tracking-wider">Clocked in</p>
                        <p class="text-white text-sm font-bold mt-0.5">
                            {{ $attendance?->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('h:i A') : '—' }}
                        </p>
                    </div>
                    <div class="bg-white/10 rounded-xl px-3 py-2">
                        <p class="text-white/60 text-[10px] font-bold uppercase tracking-wider">Worked</p>
                        <p class="text-white text-sm font-bold mt-0.5 font-mono">
                            {{ sprintf('%dh %02dm', $hh, $mm) }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Action CTA --}}
            <a href="{{ \Route::has('employee.attendance.index') ? route('employee.attendance.index', $tenantSlug) : '#' }}"
               class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm transition-all
                      bg-white text-gray-900 hover:bg-white/95 hover:scale-[1.02]"
               style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                @if($clockStatus === 'not_clocked_in')
                    <i data-lucide="fingerprint" class="w-4 h-4"></i> Clock In Now
                @elseif($clockStatus === 'clocked_in')
                    <i data-lucide="log-out" class="w-4 h-4"></i> Clock Out
                @elseif($clockStatus === 'on_break')
                    <i data-lucide="play" class="w-4 h-4"></i> End Break
                @else
                    <i data-lucide="check-circle" class="w-4 h-4"></i> View Today's Record
                @endif
            </a>
        </div>
    </div>

    {{-- Shift progress bar --}}
    @if($shift && $shiftProgress !== null)
        <div class="relative z-10 mt-5">
            <div class="flex items-center justify-between text-white/70 text-[11px] font-bold mb-1.5">
                <span>Shift progress</span>
                <span>{{ $shiftProgress }}%</span>
            </div>
            <div class="w-full h-1.5 rounded-full bg-white/20 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-white to-white/70 transition-all"
                     style="width: {{ $shiftProgress }}%"></div>
            </div>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     TODAY'S STATS — 4 quick KPIs
═══════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="lmt-stat" data-lmt-anim="fade-up">
        <div class="flex-1">
            <p class="lmt-stat-label">Today's Hours</p>
            <p class="lmt-stat-value font-mono">{{ sprintf('%d:%02d', $hh, $mm) }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $attendance?->clock_in_at ? 'Since '.\Carbon\Carbon::parse($attendance->clock_in_at)->format('h:i A') : 'Not started' }}
            </p>
        </div>
        <div class="lmt-stat-icon bg-brand-50 text-brand-600">
            <i data-lucide="timer" class="w-5 h-5"></i>
        </div>
    </div>

    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="0.05">
        <div class="flex-1">
            <p class="lmt-stat-label">Status</p>
            <p class="lmt-stat-value text-lg">{{ $statusMeta['label'] }}</p>
            @if($attendance?->is_late)
                <p class="text-xs text-red-500 font-semibold mt-1">
                    <i data-lucide="clock" class="w-3 h-3 inline -mt-0.5"></i>
                    Late by {{ $attendance->late_minutes }} min
                </p>
            @elseif($attendance?->is_early_out)
                <p class="text-xs text-amber-500 font-semibold mt-1">
                    <i data-lucide="log-out" class="w-3 h-3 inline -mt-0.5"></i>
                    Left {{ $attendance->early_minutes }} min early
                </p>
            @else
                <p class="text-xs text-emerald-500 font-semibold mt-1">
                    <i data-lucide="check" class="w-3 h-3 inline -mt-0.5"></i>
                    On track
                </p>
            @endif
        </div>
        <div class="lmt-stat-icon bg-emerald-50 text-emerald-600">
            <i data-lucide="activity" class="w-5 h-5"></i>
        </div>
    </div>

    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="0.10">
        <div class="flex-1">
            <p class="lmt-stat-label">This Month</p>
            <p class="lmt-stat-value">
                {{ (int) ($monthStats->present_days ?? 0) }}<span class="text-base text-gray-400 font-bold">/{{ $workingDaysInMonth }}</span>
            </p>
            <p class="text-xs text-gray-400 mt-1">days present</p>
        </div>
        <div class="lmt-stat-icon bg-violet-50 text-violet-600">
            <i data-lucide="calendar-check" class="w-5 h-5"></i>
        </div>
    </div>

    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="0.15">
        <div class="flex-1">
            <p class="lmt-stat-label">Month Hours</p>
            <p class="lmt-stat-value font-mono">{{ number_format((float) ($monthStats->total_hours ?? 0), 1) }}h</p>
            @if(($monthStats->overtime_hours ?? 0) > 0)
                <p class="text-xs text-emerald-500 font-semibold mt-1">
                    +{{ number_format($monthStats->overtime_hours, 1) }}h overtime
                </p>
            @else
                <p class="text-xs text-gray-400 mt-1">No overtime</p>
            @endif
        </div>
        <div class="lmt-stat-icon bg-amber-50 text-amber-600">
            <i data-lucide="trending-up" class="w-5 h-5"></i>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     MAIN GRID — Left column (2/3) + Right column (1/3)
═══════════════════════════════════════════════════════════════════════ --}}
<div class="grid lg:grid-cols-[2fr_1fr] gap-6">

    {{-- ════════════ LEFT COLUMN ════════════ --}}
    <div class="space-y-6">

        {{-- Last 7 Days Strip --}}
        <div class="lmt-card" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-base font-black text-gray-900">Last 7 Days</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Your attendance pattern at a glance</p>
                </div>
                @if(\Route::has('employee.attendance.index'))
                    <a href="{{ route('employee.attendance.index', $tenantSlug) }}"
                       class="text-xs font-bold transition-colors" style="color:var(--brand-500);">
                        View all
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-7 gap-2">
                @foreach($last7 as $day)
                    @php
                        $dotStyle = match($day->dot) {
                            'present' => 'background:#10b981',
                            'late'    => 'background:#f59e0b',
                            'absent'  => 'background:#ef4444',
                            'leave'   => 'background:#8b5cf6',
                            'half'    => 'background:#3b82f6',
                            'holiday' => 'background:#06b6d4',
                            'weekend' => 'background:#cbd5e1',
                            default   => 'background:transparent;border:2px dashed #cbd5e1',
                        };
                    @endphp
                    <div class="text-center p-3 rounded-xl transition-all hover:scale-105 cursor-pointer
                                {{ $day->is_today ? 'ring-2' : 'bg-gray-50 dark:bg-slate-800' }}"
                         @if($day->is_today) style="background:var(--brand-50); --tw-ring-color: var(--brand-500);" @endif
                         title="{{ $day->date->format('D, M j') }} · {{ $day->tooltip }}">
                        <p class="text-[10px] font-bold uppercase tracking-wide
                                  {{ $day->is_today ? '' : 'text-gray-400' }}"
                           @if($day->is_today) style="color:var(--brand-700);" @endif>
                            {{ $day->date->format('D') }}
                        </p>
                        <p class="text-base font-black mt-1
                                  {{ $day->is_today ? '' : 'text-gray-900 dark:text-slate-100' }}"
                           @if($day->is_today) style="color:var(--brand-600);" @endif>
                            {{ $day->date->format('j') }}
                        </p>
                        <div class="w-2 h-2 rounded-full mx-auto mt-2" style="{{ $dotStyle }}"></div>
                        @if($day->hours > 0)
                            <p class="text-[9px] text-gray-400 font-mono mt-1">{{ number_format($day->hours, 1) }}h</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="flex items-center justify-center gap-4 mt-5 flex-wrap text-[11px] text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Present</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Late</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>Absent</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-violet-500"></span>Leave</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-cyan-500"></span>Holiday</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-300"></span>Weekend</span>
            </div>
        </div>

        {{-- Leave Balances --}}
        @if($leaveBalances->isNotEmpty())
            <div class="lmt-card" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-base font-black text-gray-900">Leave Balances</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Available days for {{ now()->year }}</p>
                    </div>
                    @if(\Route::has('employee.leaves.create'))
                        <a href="{{ route('employee.leaves.create', $tenantSlug) }}"
                           class="lmt-btn-primary lmt-btn-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            Apply Leave
                        </a>
                    @endif
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($leaveBalances as $b)
                        <div class="border border-gray-100 dark:border-slate-700 rounded-2xl p-4 transition-all hover:shadow-soft">
                            <div class="flex items-start justify-between mb-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider truncate">{{ $b->name }}</p>
                                    <p class="text-2xl font-black text-gray-900 dark:text-slate-100 mt-1 font-mono">
                                        {{ $b->available }}
                                        <span class="text-sm text-gray-400 font-bold">/ {{ $b->total }}</span>
                                    </p>
                                </div>
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                     style="background:{{ $b->color }}20; color:{{ $b->color }};">
                                    <i data-lucide="calendar-off" class="w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     style="width: {{ $b->used_pct }}%; background:{{ $b->color }};"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-gray-400 font-semibold mt-1.5">
                                <span>{{ $b->used }} used</span>
                                @if($b->pending > 0)
                                    <span class="text-amber-500">{{ $b->pending }} pending</span>
                                @else
                                    <span>{{ $b->used_pct }}% used</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($pendingLeavesCount > 0)
                    <div class="mt-4 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 flex items-center gap-3">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-600 flex-shrink-0"></i>
                        <p class="text-xs font-semibold text-amber-800 dark:text-amber-300 flex-1">
                            You have {{ $pendingLeavesCount }} leave request{{ $pendingLeavesCount > 1 ? 's' : '' }} pending approval.
                        </p>
                        @if(\Route::has('employee.leaves.index'))
                            <a href="{{ route('employee.leaves.index', $tenantSlug) }}"
                               class="text-xs font-bold text-amber-700 dark:text-amber-300 hover:underline whitespace-nowrap">
                                View
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- My Upcoming Tasks --}}
        @if($upcomingTasks->isNotEmpty())
            <div class="lmt-card" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-black text-gray-900">My Tasks</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Coming up soon</p>
                    </div>
                    @if(\Route::has('employee.tasks.index'))
                        <a href="{{ route('employee.tasks.index', $tenantSlug) }}"
                           class="text-xs font-bold transition-colors" style="color:var(--brand-500);">
                            View board
                        </a>
                    @endif
                </div>

                <div class="space-y-2">
                    @foreach($upcomingTasks as $task)
                        @php
                            $priorityColor = match($task->priority) {
                                'urgent', 'high'   => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
                                'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                                default  => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
                            };
                            $statusColor = match($task->status) {
                                'in_progress', 'doing'   => 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
                                'review'                 => 'bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
                                'blocked'                => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
                                default                  => 'bg-gray-50 text-gray-600 dark:bg-slate-700 dark:text-slate-300',
                            };
                        @endphp
                        <a href="{{ \Route::has('employee.tasks.show') ? route('employee.tasks.show', [$tenantSlug, $task->id]) : '#' }}"
                           class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-slate-700 hover:border-brand-200 dark:hover:border-brand-200 hover:shadow-soft transition-all">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $statusColor }}">
                                <i data-lucide="check-square" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate">{{ $task->title }}</p>
                                <div class="flex items-center gap-2 mt-0.5 text-xs">
                                    <span class="px-1.5 py-0.5 rounded-full font-bold {{ $priorityColor }}">{{ ucfirst($task->priority) }}</span>
                                    @if($task->due_label)
                                        <span class="text-gray-400 {{ $task->overdue ? 'text-red-500 font-bold' : '' }}">
                                            <i data-lucide="clock" class="w-3 h-3 inline -mt-0.5"></i>
                                            {{ $task->overdue ? 'Overdue ' : '' }}{{ $task->due_label }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════ RIGHT COLUMN ════════════ --}}
    <div class="space-y-6">

        {{-- Announcements --}}
        <div class="lmt-card" data-lmt-anim="fade-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-black text-gray-900 flex items-center gap-2">
                    <i data-lucide="megaphone" class="w-4 h-4" style="color:var(--brand-500);"></i>
                    Announcements
                </h3>
                @if(\Route::has('employee.announcements.index'))
                    <a href="{{ route('employee.announcements.index', $tenantSlug) }}"
                       class="text-xs font-bold transition-colors" style="color:var(--brand-500);">
                        All
                    </a>
                @endif
            </div>

            @if($announcements->isEmpty())
                <div class="text-center py-8 text-sm text-gray-400">
                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                    No announcements yet
                </div>
            @else
                <div class="space-y-3">
                    @foreach($announcements as $a)
                        <div class="flex items-start gap-3 pb-3 border-b border-gray-100 dark:border-slate-700 last:border-0 last:pb-0">
                            <div class="relative w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background:var(--brand-50); color:var(--brand-600);">
                                <i data-lucide="bell" class="w-4 h-4"></i>
                                @if($a->unread)
                                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-800"></span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-slate-100 leading-snug">{{ $a->title }}</p>
                                @if($a->excerpt)
                                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 leading-relaxed">{{ $a->excerpt }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-1.5 font-semibold uppercase tracking-wide">{{ $a->when }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Upcoming Leaves --}}
        @if($upcomingLeaves->isNotEmpty())
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-base font-black text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="calendar-clock" class="w-4 h-4" style="color:var(--brand-500);"></i>
                    Upcoming Leaves
                </h3>
                <div class="space-y-3">
                    @foreach($upcomingLeaves as $lr)
                        @php
                            $color = $lr->leaveType?->color ?? '#6C7DF7';
                            $start = \Carbon\Carbon::parse($lr->start_date);
                            $end   = \Carbon\Carbon::parse($lr->end_date);
                        @endphp
                        <div class="flex items-start gap-3 p-3 rounded-xl"
                             style="background:{{ $color }}0d; border-left:3px solid {{ $color }};">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background:{{ $color }}1a; color:{{ $color }};">
                                <i data-lucide="palmtree" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-slate-100">{{ $lr->leaveType?->name ?? 'Leave' }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                    {{ $start->format('M j') }} {{ $start->ne($end) ? ' '.$end->format('M j') : '' }}
                                    · {{ $lr->total_days }} day{{ $lr->total_days > 1 ? 's' : '' }}
                                </p>
                                <p class="text-[10px] text-gray-400 mt-1 font-semibold uppercase tracking-wide">
                                    Starts {{ $start->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Birthdays This Week --}}
        @if($birthdaysThisWeek->isNotEmpty())
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-base font-black text-gray-900 mb-4 flex items-center gap-2">
                     <span>Birthdays This Week</span>
                </h3>
                <div class="space-y-2.5">
                    @foreach($birthdaysThisWeek as $bd)
                        <div class="flex items-center gap-3">
                            <img src="{{ $bd->avatar }}" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0"/>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate">{{ $bd->name }}</p>
                                <p class="text-xs text-gray-400">{{ $bd->date }}</p>
                            </div>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full
                                         {{ $bd->when === 'Today' ? 'bg-pink-100 text-pink-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $bd->when }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="lmt-card" data-lmt-anim="fade-up">
            <h3 class="text-base font-black text-gray-900 mb-4">Quick Actions</h3>
            @php
                $quickActions = [
                    ['icon'=>'calendar-off', 'label'=>'Apply Leave',     'color'=>'#8b5cf6', 'route'=>'employee.leaves.create'],
                    ['icon'=>'receipt',      'label'=>'Latest Payslip',  'color'=>'#10b981', 'route'=>'employee.payslips.index'],
                    ['icon'=>'wallet',       'label'=>'Submit Expense',  'color'=>'#f59e0b', 'route'=>'employee.expenses.create'],
                    ['icon'=>'life-buoy',    'label'=>'Get Support',     'color'=>'#ef4444', 'route'=>'employee.helpdesk.create'],
                ];
            @endphp
            <div class="grid grid-cols-2 gap-2.5">
                @foreach($quickActions as $a)
                    @php $exists = \Route::has($a['route']); @endphp
                    <a href="{{ $exists ? route($a['route'], $tenantSlug) : '#' }}"
                       class="flex flex-col items-start gap-2 p-3 rounded-xl border border-gray-100 dark:border-slate-700 transition-all
                              {{ $exists ? 'hover:shadow-soft hover:-translate-y-0.5' : 'opacity-50 cursor-not-allowed' }}"
                       @if(!$exists) onclick="event.preventDefault()" @endif>
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                             style="background:{{ $a['color'] }}1a; color:{{ $a['color'] }};">
                            <i data-lucide="{{ $a['icon'] }}" class="w-4 h-4"></i>
                        </div>
                        <p class="text-xs font-bold text-gray-900 dark:text-slate-100">{{ $a['label'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endif {{-- end !noEmployee --}}

@push('scripts')
<script>
    // Live clock used in the hero (re-runs every second, in the tenant's timezone)
    function liveClock() {
        return {
            clock: '',
            ampm: '',
            zone: '',
            timer: null,
            tz: @json($tenantTimezone ?? config('app.timezone')),
            start() {
                this.update();
                this.timer = setInterval(() => this.update(), 1000);
            },
            update() {
                const parts = new Intl.DateTimeFormat('en-US', {
                    timeZone: this.tz,
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
                }).formatToParts(new Date());
                const get = (t) => parts.find(p => p.type === t)?.value ?? '';
                this.clock = `${get('hour')}:${get('minute')}`;
                this.ampm  = get('dayPeriod');
                this.zone  = this.tz.replace(/_/g, ' ');
            },
        };
    }
</script>
@endpush

@endsection