@extends('layouts.employee')

@section('title', 'My Attendance')
@section('page-title', 'My Attendance')

@push('head')
<style>
    /* Day cell sizing tweaks */
    .cal-cell { aspect-ratio: 1 / 1; min-height: 64px; }
    @media (min-width: 1024px) { .cal-cell { min-height: 88px; } }

    /* Status colors used in calendar + list */
    .st-present { background:#10b981; }
    .st-late    { background:#f59e0b; }
    .st-absent  { background:#ef4444; }
    .st-leave   { background:#8b5cf6; }
    .st-half    { background:#3b82f6; }
    .st-holiday { background:#06b6d4; }
    .st-weekend { background:#cbd5e1; }
    .st-future  { background:transparent; border:1px dashed #e5e7eb; }
    .st-none    { background:#e5e7eb; }

    /* Drawer */
    .drawer-backdrop { background:rgba(15,23,42,.45); backdrop-filter:blur(4px); }
    .drawer-panel   { max-width:480px; }
</style>
@endpush

@section('content')

@php
    $prevMonth = $month->copy()->subMonth()->format('Y-m');
    $nextMonth = $month->copy()->addMonth()->format('Y-m');
    $thisMonth = \Carbon\Carbon::today()->startOfMonth()->format('Y-m');
    $isCurrent = $month->format('Y-m') === $thisMonth;
@endphp

<div x-data="attendancePage()" x-init="init()">

    @include('employee.attendance._clock_card', ['shift' => $todayShift, 'rec' => $todayRec])

    {{-- ═══════════════════════════════════════════════════════════════
         MONTHLY SUMMARY
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 lg:gap-4 mb-6">
        @php
            $cards = [
                ['lbl'=>'Present',  'val'=>$summary->present_days,  'icon'=>'check-circle','color'=>'bg-emerald-50 text-emerald-600'],
                ['lbl'=>'Late',     'val'=>$summary->late_count,    'icon'=>'alert-circle','color'=>'bg-amber-50 text-amber-600'],
                ['lbl'=>'Absent',   'val'=>$summary->absent_days,   'icon'=>'x-circle',    'color'=>'bg-red-50 text-red-600'],
                ['lbl'=>'On Leave', 'val'=>$summary->leave_days,    'icon'=>'palmtree',    'color'=>'bg-violet-50 text-violet-600'],
                ['lbl'=>'Hours',    'val'=>format_hours($summary->total_hours, '0h'), 'icon'=>'clock','color'=>'bg-brand-50 text-brand-600'],
            ];
        @endphp
        @foreach($cards as $i => $c)
            <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.04 }}">
                <div class="flex-1">
                    <p class="lmt-stat-label">{{ $c['lbl'] }}</p>
                    <p class="lmt-stat-value">{{ $c['val'] }}</p>
                </div>
                <div class="lmt-stat-icon {{ $c['color'] }}">
                    <i data-lucide="{{ $c['icon'] }}" class="w-5 h-5"></i>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TOOLBAR — month picker + view switcher + export
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card mb-6" data-lmt-anim="fade-up">
        <div class="flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">

            {{-- Month nav --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('employee.attendance.index', [$tenantSlug, 'month' => $prevMonth, 'view' => $view]) }}"
                   class="w-9 h-9 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center transition-colors">
                    <i data-lucide="chevron-left" class="w-4 h-4 text-gray-800"></i>
                </a>
                <div class="text-base lg:text-lg font-black text-gray-900 dark:text-slate-100 min-w-[170px] text-center">
                    {{ $month->format('F Y') }}
                </div>
                <a href="{{ route('employee.attendance.index', [$tenantSlug, 'month' => $nextMonth, 'view' => $view]) }}"
                   class="w-9 h-9 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center transition-colors">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-800"></i>
                </a>
                @if(! $isCurrent)
                    <a href="{{ route('employee.attendance.index', [$tenantSlug, 'view' => $view]) }}"
                       class="hidden lg:inline-flex text-xs font-bold ml-2 px-2.5 py-1 rounded-full"
                       style="background:var(--brand-50); color:var(--brand-700);">
                        Today
                    </a>
                @endif
            </div>

            {{-- View switcher + export --}}
            <div class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex p-1 bg-gray-100 dark:bg-slate-800 rounded-xl text-xs font-bold">
                    <a href="{{ route('employee.attendance.index', [$tenantSlug, 'view' => 'calendar', 'month' => $month->format('Y-m')]) }}"
                       class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition
                              {{ $view === 'calendar' ? 'bg-white dark:bg-slate-900 shadow text-gray-900 dark:text-white' : 'text-gray-800' }}">
                        <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i> Calendar
                    </a>
                    <a href="{{ route('employee.attendance.index', [$tenantSlug, 'view' => 'list', 'month' => $month->format('Y-m')]) }}"
                       class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition
                              {{ $view === 'list' ? 'bg-white dark:bg-slate-900 shadow text-gray-900 dark:text-white' : 'text-gray-800' }}">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i> List
                    </a>
                </div>

                <a href="{{ route('employee.attendance.export', [$tenantSlug, 'month' => $month->format('Y-m')]) }}"
                   class="lmt-btn-secondary lmt-btn-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Export CSV
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         VIEW BODY
    ═══════════════════════════════════════════════════════════════ --}}
    @if($view === 'calendar')
        @include('employee.attendance._calendar')
    @else
        @include('employee.attendance._list')
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         DAY DETAIL DRAWER (rendered on demand via AJAX)
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
                <i data-lucide="x" class="w-5 h-5 text-gray-800"></i>
            </button>

            <div x-show="drawerLoading" class="flex items-center justify-center py-20 text-sm text-gray-800 gap-2">
                <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Loading day details…
            </div>

            <div x-show="!drawerLoading" x-html="drawerHtml"></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         CLOCK-IN / CLOCK-OUT MODAL
    ═══════════════════════════════════════════════════════════════ --}}
    @include('employee.attendance._clockin_modal')

    @include('employee.attendance._break_modal')

</div>{{-- /attendancePage --}}

@include('employee.attendance._clock_scripts')

@endsection