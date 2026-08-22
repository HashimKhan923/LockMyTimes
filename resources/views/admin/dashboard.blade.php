@extends('layouts.admin')
@section('title','Dashboard')
@section('page-title','Dashboard')

@section('content')

{{-- ============================================================
     WELCOME BANNER
============================================================ --}}
<div class="rounded-2xl p-6 mb-7 relative overflow-hidden"
     style="background:linear-gradient(135deg,#6C7DF7 0%,#4A5BE8 60%,#7C3AED 100%);">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5"></div>
        <div class="absolute top-4 right-1/4 w-32 h-32 rounded-full bg-white/3"></div>
    </div>
    <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <p class="text-white/65 text-sm font-medium">
                {{ $today->format('l, F j, Y') }}
            </p>
            <h1 class="text-white text-2xl font-black mt-1">
                {{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }},
                {{ Auth::user()->name ?? 'Admin' }}
            </h1>
            <p class="text-white/65 text-sm mt-1">
                @if($totalPending > 0)
                    You have <strong class="text-white">{{ $totalPending }} pending approval{{ $totalPending > 1 ? 's' : '' }}</strong> waiting for your review.
                @else
                    Everything is up to date. Have a great day!
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
            @if($birthdaysToday->count() > 0)
            <div class="bg-white/15 border border-white/25 rounded-xl px-4 py-2.5 text-center">
                <p class="text-2xl"></p>
                <p class="text-white text-xs font-bold mt-1">{{ $birthdaysToday->count() }} birthday{{ $birthdaysToday->count() > 1 ? 's' : '' }}</p>
            </div>
            @endif
            <div class="bg-white/15 border border-white/25 rounded-xl px-4 py-2.5 text-center">
                <p class="text-white text-2xl font-black">{{ $attendanceRate }}%</p>
                <p class="text-white/70 text-xs mt-0.5">Attendance today</p>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     KPI STAT CARDS — ROW 1
============================================================ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-7">

    {{-- Total Employees --}}
    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="0">
        <div class="flex-1">
            <p class="lmt-stat-label">Total Employees</p>
            <p class="lmt-stat-value">{{ number_format($totalEmployees) }}</p>
            <p class="lmt-stat-delta-up">
                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                +{{ $newThisMonth }} this month
            </p>
        </div>
        <div class="lmt-stat-icon bg-brand-50 text-brand-600">
            <i data-lucide="users" class="w-5 h-5"></i>
        </div>
    </div>

    {{-- Present Today --}}
    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="0.07">
        <div class="flex-1">
            <p class="lmt-stat-label">Present Today</p>
            <p class="lmt-stat-value">{{ $presentToday }}</p>
            <p class="{{ $attendanceRate >= 80 ? 'lmt-stat-delta-up' : 'lmt-stat-delta-down' }}">
                <i data-lucide="{{ $attendanceRate >= 80 ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5"></i>
                {{ $attendanceRate }}% attendance rate
            </p>
        </div>
        <div class="lmt-stat-icon bg-emerald-50 text-emerald-600">
            <i data-lucide="user-check" class="w-5 h-5"></i>
        </div>
    </div>

    {{-- Pending Approvals --}}
    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="0.14">
        <div class="flex-1">
            <p class="lmt-stat-label">Pending Approvals</p>
            <p class="lmt-stat-value">{{ $totalPending }}</p>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                @if($pendingLeaves > 0)
                <span class="lmt-badge-amber text-xs">{{ $pendingLeaves }} leave</span>
                @endif
                @if($pendingExpenses > 0)
                <span class="lmt-badge-brand text-xs">{{ $pendingExpenses }} exp</span>
                @endif
                @if($pendingLoans > 0)
                <span class="lmt-badge-red text-xs">{{ $pendingLoans }} loan</span>
                @endif
                @if($totalPending === 0)
                <span class="lmt-badge-green text-xs">All clear </span>
                @endif
            </div>
        </div>
        <div class="lmt-stat-icon bg-amber-50 text-amber-600">
            <i data-lucide="clock" class="w-5 h-5"></i>
        </div>
    </div>

    {{-- Active Projects --}}
    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="0.21">
        <div class="flex-1">
            <p class="lmt-stat-label">Active Projects</p>
            <p class="lmt-stat-value">{{ $activeProjects }}</p>
            <p class="{{ $overdueTasks > 0 ? 'lmt-stat-delta-down' : 'lmt-stat-delta-up' }}">
                <i data-lucide="{{ $overdueTasks > 0 ? 'alert-triangle' : 'check-circle' }}" class="w-3.5 h-3.5"></i>
                {{ $overdueTasks > 0 ? $overdueTasks.' overdue tasks' : 'No overdue tasks' }}
            </p>
        </div>
        <div class="lmt-stat-icon bg-purple-50 text-purple-600">
            <i data-lucide="kanban" class="w-5 h-5"></i>
        </div>
    </div>
</div>

{{-- ============================================================
     ROW 2 — Attendance Chart + Quick Actions
============================================================ --}}
<div class="grid lg:grid-cols-3 gap-6 mb-7">

    {{-- Attendance weekly chart --}}
    <div class="lg:col-span-2 lmt-card" data-lmt-anim="fade-up">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-black text-gray-900 text-base">Attendance This Week</h3>
                <p class="text-xs text-gray-800 mt-0.5">Daily presence across the team</p>
            </div>
            <a href="{{ route('admin.attendance.index', $currentTenant->slug) }}"
               class="text-xs font-bold text-brand-500 hover:text-brand-700 flex items-center gap-1">
                Full report <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        {{-- Chart bars --}}
        <div class="flex items-end gap-3 h-32">
            @foreach($attendanceChart as $day)
            <div class="flex-1 flex flex-col items-center gap-2">
                <span class="text-xs font-bold" style="color:{{ $day['rate'] >= 80 ? '#10B981' : ($day['rate'] >= 60 ? '#F59E0B' : '#EF4444') }}">
                    {{ $day['total'] > 0 ? $day['rate'].'%' : '—' }}
                </span>
                <div class="w-full rounded-t-lg transition-all duration-700 relative overflow-hidden"
                     style="height:{{ max($day['rate'], 4) }}%; background:{{ $day['date'] === today()->toDateString() ? 'linear-gradient(180deg,#6C7DF7,#4A5BE8)' : '#EEF0FE' }};">
                    @if($day['date'] === today()->toDateString())
                    <div class="absolute inset-0 opacity-30" style="background:linear-gradient(180deg,#fff 0%,transparent 100%);"></div>
                    @endif
                </div>
                <span class="text-xs font-semibold {{ $day['date'] === today()->toDateString() ? 'text-brand-600' : 'text-gray-800' }}">
                    {{ $day['label'] }}
                </span>
            </div>
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-xl font-black text-emerald-600">{{ $presentToday }}</p>
                <p class="text-xs text-gray-800">Present</p>
            </div>
            <div>
                <p class="text-xl font-black text-amber-500">{{ $onLeaveToday }}</p>
                <p class="text-xs text-gray-800">On Leave</p>
            </div>
            <div>
                <p class="text-xl font-black text-red-500">{{ $lateToday }}</p>
                <p class="text-xs text-gray-800">Late</p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="lmt-card" data-lmt-anim="fade-up" data-lmt-delay="0.08">
        <h3 class="font-black text-gray-900 text-base mb-4">Quick Actions</h3>
        <div class="space-y-2">
            @php
            $actions = [
                ['label'=>'Add Employee',       'icon'=>'user-plus',    'color'=>'bg-brand-50 text-brand-600 group-hover:bg-brand-500 group-hover:text-white', 'route'=>'admin.employees.create'],
                ['label'=>'Approve Leaves',     'icon'=>'calendar-check','color'=>'bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white', 'route'=>'admin.leaves.index'],
                ['label'=>'Run Payroll',        'icon'=>'dollar-sign',  'color'=>'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-500 group-hover:text-white', 'route'=>'admin.payroll.index'],
                ['label'=>'Generate QR Code',   'icon'=>'qr-code',      'color'=>'bg-purple-50 text-purple-600 group-hover:bg-purple-500 group-hover:text-white', 'route'=>'admin.qrcodes.index'],
                ['label'=>'New Announcement',   'icon'=>'megaphone',    'color'=>'bg-rose-50 text-rose-600 group-hover:bg-rose-500 group-hover:text-white', 'route'=>'admin.announcements.index'],
                ['label'=>'Create Project',     'icon'=>'plus-circle',  'color'=>'bg-cyan-50 text-cyan-600 group-hover:bg-cyan-500 group-hover:text-white', 'route'=>'admin.projects.index'],
            ];
            @endphp
            @foreach($actions as $action)
            @if(\Route::has($action['route']))
            <a href="{{ route($action['route'], $currentTenant->slug) }}"
               class="group flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-all">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-all {{ $action['color'] }}">
                    <i data-lucide="{{ $action['icon'] }}" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700 group-hover:text-gray-900">{{ $action['label'] }}</span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-800 ml-auto group-hover:text-gray-800 transition-colors"></i>
            </a>
            @else
            <div class="flex items-center gap-3 p-3 rounded-xl opacity-50">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center {{ $action['color'] }}">
                    <i data-lucide="{{ $action['icon'] }}" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700">{{ $action['label'] }}</span>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>

{{-- ============================================================
     ROW 3 — Recent Clock-ins + Pending Leaves + Dept Chart
============================================================ --}}
<div class="grid lg:grid-cols-3 gap-6 mb-7">

    {{-- Recent clock-ins --}}
    <div class="lmt-card" data-lmt-anim="fade-up">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-black text-gray-900 text-base">Today's Clock-ins</h3>
            <a href="{{ route('admin.attendance.index', $currentTenant->slug) }}"
               class="text-xs font-bold text-brand-500 hover:text-brand-700 flex items-center gap-1">
                View all <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
        @forelse($recentAttendances as $att)
        <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-none">
            <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">
                {{ substr($att->employee->first_name ?? 'E', 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">
                    {{ $att->employee->full_name ?? 'Employee' }}
                </p>
                <p class="text-xs text-gray-800">
                    {{ $att->clock_in_at?->format('h:i A') ?? '—' }}
                    @if($att->is_late)
                    <span class="text-amber-500 font-semibold ml-1">Late</span>
                    @endif
                </p>
            </div>
            <div class="flex-shrink-0">
                @if($att->clock_out_at)
                <span class="lmt-badge-gray text-xs">Out {{ $att->clock_out_at->format('h:i A') }}</span>
                @else
                <span class="lmt-badge-green text-xs">● In</span>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-8">
            <i data-lucide="clock" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
            <p class="text-sm text-gray-800">No clock-ins yet today</p>
        </div>
        @endforelse
    </div>

    {{-- Pending leaves --}}
    <div class="lmt-card" data-lmt-anim="fade-up" data-lmt-delay="0.08">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-black text-gray-900 text-base">Pending Leaves</h3>
            @if($pendingLeaves > 0)
            <span class="lmt-badge-amber text-xs font-bold">{{ $pendingLeaves }} pending</span>
            @endif
        </div>
        @forelse($recentLeaves as $leave)
        <div class="flex items-start gap-3 py-3 border-b border-gray-50 last:border-none">
            <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">
                {{ substr($leave->employee->first_name ?? 'E', 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">
                    {{ $leave->employee->full_name ?? 'Employee' }}
                </p>
                <p class="text-xs text-gray-800">
                    {{ $leave->leaveType->name ?? 'Leave' }} ·
                    {{ $leave->start_date->format('M j') }}–{{ $leave->end_date->format('M j') }}
                    ({{ $leave->total_days }}d)
                </p>
            </div>
            <div class="flex gap-1.5 flex-shrink-0">
                <a href="{{ route('admin.leaves.index', $currentTenant->slug) }}"
                   class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors text-xs font-bold"
                   title="Review">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
        @empty
        <div class="text-center py-8">
            <i data-lucide="calendar-check" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
            <p class="text-sm text-gray-800">No pending leave requests</p>
        </div>
        @endforelse
    </div>

    {{-- Department chart --}}
    <div class="lmt-card" data-lmt-anim="fade-up" data-lmt-delay="0.16">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-black text-gray-900 text-base">By Department</h3>
        </div>
        @if($deptChart->isEmpty())
        <div class="text-center py-8">
            <i data-lucide="git-branch" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
            <p class="text-sm text-gray-800">No departments set up yet</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($deptChart as $dept)
            @php $pct = $totalEmployees > 0 ? round(($dept->total / $totalEmployees) * 100) : 0; @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 truncate">{{ $dept->name }}</span>
                    <span class="text-sm font-bold text-gray-900 ml-2">{{ $dept->total }}</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700"
                         style="width:{{ $pct }}%; background:{{ $dept->color ?? '#6C7DF7' }};">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ============================================================
     ROW 4 — Birthdays/Anniversaries + Kudos + Upcoming Holidays
============================================================ --}}
<div class="grid lg:grid-cols-3 gap-6 mb-7">

    {{-- Birthdays & Anniversaries --}}
    <div class="lmt-card" data-lmt-anim="fade-up">
        <h3 class="font-black text-gray-900 text-base mb-4">Today's Celebrations</h3>
        @if($birthdaysToday->isEmpty() && $anniversariesToday->isEmpty())
        <div class="text-center py-8">
            <p class="text-3xl mb-2"></p>
            <p class="text-sm text-gray-800">No celebrations today</p>
        </div>
        @else
            @foreach($birthdaysToday as $emp)
            <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-none">
                <div class="w-9 h-9 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center text-lg"></div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $emp->full_name }}</p>
                    <p class="text-xs text-gray-800">Birthday today!</p>
                </div>
            </div>
            @endforeach
            @foreach($anniversariesToday as $emp)
            @php $years = $emp->hire_date->diffInYears(today()); @endphp
            <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-none">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg"></div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $emp->full_name }}</p>
                    <p class="text-xs text-gray-800">{{ $years }}-year work anniversary!</p>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    {{-- Kudos wall --}}
    <div class="lmt-card" data-lmt-anim="fade-up" data-lmt-delay="0.08">
        <h3 class="font-black text-gray-900 text-base mb-4">Kudos Wall </h3>
        @forelse($recentKudos as $kudo)
        <div class="p-3 rounded-xl bg-gray-50 mb-2.5">
            <div class="flex items-center gap-2 mb-1.5">
                @if($kudo->badge)
                <span class="lmt-badge-brand text-xs">{{ $kudo->badge }}</span>
                @endif
                <span class="text-xs text-gray-800 ml-auto">{{ $kudo->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-sm text-gray-700 italic">"{{ Str::limit($kudo->message, 80) }}"</p>
            <div class="flex items-center gap-1 mt-2 text-xs text-gray-800">
                <span class="font-semibold text-gray-700">{{ $kudo->fromEmployee->first_name ?? '?' }}</span>
                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                <span class="font-semibold text-brand-600">{{ $kudo->toEmployee->first_name ?? '?' }}</span>
            </div>
        </div>
        @empty
        <div class="text-center py-8">
            <p class="text-3xl mb-2"></p>
            <p class="text-sm text-gray-800">No kudos yet</p>
            <p class="text-xs text-gray-800 mt-1">Start recognising your team!</p>
        </div>
        @endforelse
    </div>

    {{-- Upcoming Holidays --}}
    <div class="lmt-card" data-lmt-anim="fade-up" data-lmt-delay="0.16">
        <h3 class="font-black text-gray-900 text-base mb-4">Upcoming Holidays</h3>
        @forelse($upcomingHolidays as $holiday)
        @php $daysAway = today()->diffInDays($holiday->date, false); @endphp
        <div class="flex items-center gap-3 py-3 border-b border-gray-50 last:border-none">
            <div class="w-12 h-12 rounded-xl lmt-gradient-bg flex flex-col items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold leading-none">{{ $holiday->date->format('M') }}</span>
                <span class="text-white text-lg font-black leading-none">{{ $holiday->date->format('d') }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ $holiday->name }}</p>
                <p class="text-xs text-gray-800">
                    {{ $daysAway === 0 ? 'Today' : ($daysAway === 1 ? 'Tomorrow' : "In {$daysAway} days") }}
                    · {{ $holiday->is_paid ? 'Paid' : 'Unpaid' }}
                </p>
            </div>
            <span class="lmt-badge text-xs {{ $holiday->type === 'federal' ? 'lmt-badge-brand' : 'lmt-badge-gray' }}">
                {{ ucfirst($holiday->type) }}
            </span>
        </div>
        @empty
        <div class="text-center py-8">
            <i data-lucide="calendar" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
            <p class="text-sm text-gray-800">No upcoming holidays</p>
        </div>
        @endforelse

        @if($lastPayrollRun)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50">
                <i data-lucide="dollar-sign" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
                <div>
                    <p class="text-xs font-bold text-emerald-800">Last Payroll Run</p>
                    <p class="text-xs text-emerald-600">
                        {{ $lastPayrollRun->pay_date?->format('M j, Y') ?? 'N/A' }} ·
                        ${{ number_format($lastPayrollRun->total_net, 0) }} net
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 200);
});
</script>
@endpush