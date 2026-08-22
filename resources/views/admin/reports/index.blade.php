@extends('layouts.admin')
@section('title','Reports')
@section('page-title','Reports & Analytics')

@section('content')

{{-- Report Selector + Date Range --}}
<div class="lmt-card mb-6 p-4">
    <form action="{{ route('admin.reports.index', $tenant) }}" method="GET"
          class="flex flex-wrap items-end gap-4">
        {{-- Report type --}}
        <div class="flex-1 min-w-48">
            <label class="lmt-label text-xs">Report Type</label>
            <select name="report" class="lmt-select" onchange="this.form.submit()">
                @foreach([
                    'overview' => ' Executive Overview',
                    'headcount' => ' Headcount & Workforce',
                    'attendance'  => '⏰ Attendance',
                    'payroll' => ' Payroll',
                    'leave' => '️ Leave',
                    'performance' => ' Performance',
                    'recruitment' => ' Recruitment',
                    'expenses' => ' Expenses',
                    'training' => ' Training',
                ] as $val => $label)
                <option value="{{ $val }}" {{ $report === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Date range --}}
        <div>
            <label class="lmt-label text-xs">From</label>
            <input type="date" name="from" value="{{ $from }}" class="lmt-input py-2"/>
        </div>
        <div>
            <label class="lmt-label text-xs">To</label>
            <input type="date" name="to" value="{{ $to }}" class="lmt-input py-2"/>
        </div>

        {{-- Department filter --}}
        @if(in_array($report, ['headcount','attendance','leave']))
        <div>
            <label class="lmt-label text-xs">Department</label>
            <select name="department" class="lmt-select py-2">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <button type="submit" class="lmt-btn-primary">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            Generate
        </button>

        {{-- Quick presets --}}
        <div class="flex items-center gap-2 ml-auto">
            @foreach([
                ['This Month',  now()->startOfMonth()->toDateString(), now()->toDateString()],
                ['Last Month',  now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
                ['This Quarter',now()->startOfQuarter()->toDateString(), now()->toDateString()],
                ['This Year',   now()->startOfYear()->toDateString(), now()->toDateString()],
            ] as [$label, $f, $t])
            <a href="{{ route('admin.reports.index', $tenant) }}?report={{ $report }}&from={{ $f }}&to={{ $t }}"
               class="px-2.5 py-3 rounded-xl text-xs font-semibold border border-gray-200 text-gray-600
                      hover:border-brand-400 hover:text-brand-600 transition-colors whitespace-nowrap">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </form>
</div>

{{-- ===================================================
     OVERVIEW REPORT
=================================================== --}}
@if($report === 'overview')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Employees',   $totalEmployees,                     'users',          'bg-brand-50',   'text-brand-600'],
        ['New Hires',         $newHires,                           'user-plus',      'bg-emerald-50', 'text-emerald-600'],
        ['Payroll YTD',       '$'.number_format($payrollYTD,0),    'dollar-sign',    'bg-purple-50',  'text-purple-600'],
        ['Attendance Rate',   $attendanceRate.'%',                 'clock',          'bg-amber-50',   'text-amber-600'],
        ['Leaves Approved',   $leavesThisPeriod,                   'calendar-off',   'bg-red-50',     'text-red-600'],
        ['Expenses',          '$'.number_format($expensesTotal,0), 'receipt',        'bg-orange-50',  'text-orange-600'],
        ['Active Projects',   $activeProjects,                     'kanban',         'bg-blue-50',    'text-blue-600'],
        ['Open Positions',    $openJobs,                           'briefcase',      'bg-teal-50',    'text-teal-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Payroll trend --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">Monthly Payroll Spend</h3>
        @php $maxPay = $payrollTrend->max('amount') ?: 1; @endphp
        <div class="flex items-end gap-2 h-36">
            @foreach($payrollTrend as $m)
            <div class="flex-1 flex flex-col items-center gap-1">
                <span class="text-[10px] text-gray-800">
                    {{ $m['amount'] > 0 ? '$'.number_format($m['amount']/1000,0).'k' : '' }}
                </span>
                <div class="w-full rounded-t-lg lmt-gradient-bg transition-all"
                     style="height:{{ max(4, round($m['amount']/$maxPay*100)) }}%; min-height:4px"></div>
                <span class="text-[10px] text-gray-800 font-semibold">{{ $m['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Headcount by department --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">Headcount by Department</h3>
        @php $maxDept = $byDepartment->max('count') ?: 1; @endphp
        <div class="space-y-2.5">
            @foreach($byDepartment as $dept)
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-600 w-28 truncate font-medium">{{ $dept['name'] }}</span>
                <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full lmt-gradient-bg rounded-full"
                         style="width:{{ round($dept['count']/$maxDept*100) }}%"></div>
                </div>
                <span class="text-xs font-black text-gray-900 w-6 text-right">{{ $dept['count'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ===================================================
     HEADCOUNT REPORT
=================================================== --}}
@elseif($report === 'headcount')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Active',     $byStatus['active'] ?? 0,         'user-check',   'bg-emerald-50','text-emerald-600'],
        ['New Hires',  count($newHires),                  'user-plus',    'bg-brand-50',  'text-brand-600'],
        ['Terminated', count($terminated),                'user-minus',   'bg-red-50',    'text-red-600'],
        ['Total',      count($employees),                 'users',        'bg-gray-100',  'text-gray-700'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    {{-- By Type --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">By Employment Type</h3>
        <div class="space-y-2">
            @foreach($byType as $type => $count)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-none">
                <span class="text-sm text-gray-600 capitalize">{{ str_replace('_',' ',$type ?? 'Unknown') }}</span>
                <span class="text-sm font-black text-gray-900">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- By Gender --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">By Gender</h3>
        <div class="space-y-2">
            @foreach($byGender as $gender => $count)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-none">
                <span class="text-sm text-gray-600 capitalize">{{ $gender ?? 'Not Specified' }}</span>
                <span class="text-sm font-black text-gray-900">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tenure --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">By Tenure</h3>
        <div class="space-y-2">
            @foreach($tenureGroups as $group => $count)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-none">
                <span class="text-sm text-gray-600">{{ $group }}</span>
                <span class="text-sm font-black text-gray-900">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- New Hires table --}}
@if($newHires->isNotEmpty())
<div class="lmt-card p-0 overflow-hidden mb-5">
    <div class="p-4 border-b border-gray-100">
        <h3 class="font-black text-gray-900">New Hires ({{ $newHires->count() }})</h3>
    </div>
    <table class="lmt-table">
        <thead><tr><th>Employee</th><th>Department</th><th>Position</th><th>Hire Date</th></tr></thead>
        <tbody>
            @foreach($newHires as $emp)
            <tr>
                <td><div class="flex items-center gap-2"><div class="lmt-avatar-sm font-bold text-xs">{{ substr($emp->first_name,0,1) }}</div><span class="text-sm font-semibold text-gray-900">{{ $emp->full_name }}</span></div></td>
                <td class="text-sm text-gray-600">{{ $emp->department?->name ?? '—' }}</td>
                <td class="text-sm text-gray-600">{{ $emp->position?->title ?? '—' }}</td>
                <td class="text-sm text-gray-600">{{ $emp->hire_date?->format('M j, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ===================================================
     ATTENDANCE REPORT
=================================================== --}}
@elseif($report === 'attendance')

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['Present Days',    $summary['present'],              'check-circle', 'bg-emerald-50','text-emerald-600'],
        ['Absent Days',     $summary['absent'],               'x-circle',     'bg-red-50',    'text-red-600'],
        ['Late Arrivals',   $summary['late'],                 'clock',        'bg-amber-50',  'text-amber-600'],
        ['Overtime Hrs',    number_format($summary['overtime'],1), 'trending-up','bg-purple-50','text-purple-600'],
        ['Attendance Rate', $summary['rate'].'%',             'percent',      'bg-brand-50',  'text-brand-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Per-employee table --}}
<div class="lmt-card p-0 overflow-hidden">
    <div class="p-4 border-b border-gray-100">
        <h3 class="font-black text-gray-900">Employee Attendance Summary</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th class="text-center">Present</th>
                    <th class="text-center">Absent</th>
                    <th class="text-center">Late</th>
                    <th class="text-center">OT (hrs)</th>
                    <th class="text-center">Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perEmployee as $row)
                <tr>
                    <td>
                        <p class="font-semibold text-gray-900 text-sm">{{ $row['name'] }}</p>
                        <p class="text-xs text-gray-800">{{ $row['department'] }}</p>
                    </td>
                    <td class="text-center text-sm font-bold text-emerald-600">{{ $row['present'] }}</td>
                    <td class="text-center text-sm font-bold text-red-500">{{ $row['absent'] }}</td>
                    <td class="text-center text-sm font-bold text-amber-600">{{ $row['late'] }}</td>
                    <td class="text-center text-sm text-gray-700">{{ $row['overtime'] }}</td>
                    <td class="text-center">
                        <div class="flex items-center gap-2 justify-center">
                            <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $row['rate'] >= 90 ? 'bg-emerald-500' : ($row['rate'] >= 75 ? 'bg-amber-500' : 'bg-red-500') }}"
                                     style="width:{{ $row['rate'] }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-gray-700">{{ $row['rate'] }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-10 text-gray-800">No attendance data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================
     PAYROLL REPORT
=================================================== --}}
@elseif($report === 'payroll')

<div class="flex justify-end mb-4">
    @include('exports.buttons', ['route' => 'admin.reports.payroll.export', 'params' => [$tenant], 'extra' => ['from' => $from, 'to' => $to]])
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['Payroll Runs',     $totals['runs'],                          'layers',       'bg-brand-50',  'text-brand-600'],
        ['Total Gross',      '$'.number_format($totals['gross'],0),    'trending-up',  'bg-gray-100',  'text-gray-700'],
        ['Total Taxes',      '$'.number_format($totals['taxes'],0),    'percent',      'bg-red-50',    'text-red-600'],
        ['Total Deductions', '$'.number_format($totals['deductions'],0),'minus-circle','bg-amber-50',  'text-amber-600'],
        ['Net Paid',         '$'.number_format($totals['net'],0),      'dollar-sign',  'bg-emerald-50','text-emerald-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-lg font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Monthly breakdown --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100"><h3 class="font-black text-gray-900">Monthly Payroll</h3></div>
        <table class="lmt-table">
            <thead><tr><th>Month</th><th>Gross</th><th>Net</th><th>Employees</th></tr></thead>
            <tbody>
                @forelse($monthly as $m)
                <tr>
                    <td class="font-semibold text-gray-900 text-sm">{{ $m['label'] }}</td>
                    <td class="text-sm text-gray-700">${{ number_format($m['gross'],0) }}</td>
                    <td class="text-sm font-bold text-emerald-600">${{ number_format($m['net'],0) }}</td>
                    <td class="text-sm text-gray-600">{{ $m['count'] }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-8 text-gray-800">No payroll runs in period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- By department --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100"><h3 class="font-black text-gray-900">Net Pay by Department</h3></div>
        <table class="lmt-table">
            <thead><tr><th>Department</th><th>Employees</th><th>Avg Net</th><th>Total Net</th></tr></thead>
            <tbody>
                @forelse($byDept as $d)
                <tr>
                    <td class="font-semibold text-gray-900 text-sm">{{ $d['dept'] }}</td>
                    <td class="text-sm text-gray-600">{{ $d['count'] }}</td>
                    <td class="text-sm text-gray-600">${{ number_format($d['avg'],0) }}</td>
                    <td class="text-sm font-bold text-emerald-600">${{ number_format($d['total'],0) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-8 text-gray-800">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================
     LEAVE REPORT
=================================================== --}}
@elseif($report === 'leave')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Requests', $summary['total'],               'calendar-check','bg-brand-50',  'text-brand-600'],
        ['Total Days',     number_format($summary['total_days'],1), 'calendar','bg-amber-50','text-amber-600'],
        ['Types',          $summary['by_type']->count(),   'tag',           'bg-purple-50', 'text-purple-600'],
        ['Top Employees',  $topTakers->count(),             'users',         'bg-gray-100',  'text-gray-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- By type --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">By Leave Type</h3>
        @php $maxDays = $summary['by_type']->max('days') ?: 1; @endphp
        <div class="space-y-3">
            @foreach($summary['by_type'] as $lt)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="font-medium text-gray-700">{{ $lt['name'] }}</span>
                    <span class="font-bold text-gray-900">{{ $lt['days'] }} days ({{ $lt['count'] }})</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full"
                         style="width:{{ round($lt['days']/$maxDays*100) }}%; background:{{ $lt['color'] }}"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top leave takers --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100"><h3 class="font-black text-gray-900">Top Leave Takers</h3></div>
        <table class="lmt-table">
            <thead><tr><th>Employee</th><th>Department</th><th class="text-center">Requests</th><th class="text-center">Days</th></tr></thead>
            <tbody>
                @foreach($topTakers as $t)
                <tr>
                    <td class="text-sm font-semibold text-gray-900">{{ $t['name'] }}</td>
                    <td class="text-xs text-gray-800">{{ $t['dept'] }}</td>
                    <td class="text-center text-sm font-bold text-gray-900">{{ $t['count'] }}</td>
                    <td class="text-center text-sm font-bold text-brand-600">{{ $t['days'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================
     PERFORMANCE REPORT
=================================================== --}}
@elseif($report === 'performance')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Reviews Done',  count($reviews),                       'check-circle','bg-emerald-50','text-emerald-600'],
        ['Avg Rating',    $avgRating ? number_format($avgRating,2).' / 5' : '—', 'star','bg-amber-50','text-amber-600'],
        ['Active Goals',  $goals['in_progress'] ?? 0,            'target',      'bg-brand-50',  'text-brand-600'],
        ['Goals Done',    $goals['completed'] ?? 0,              'check-square','bg-purple-50', 'text-purple-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Rating distribution --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-5">Rating Distribution</h3>
        @php $maxRating = $ratingDist->max() ?: 1; @endphp
        @foreach([5=>'Outstanding',4=>'Exceeds Expectations',3=>'Meets Expectations',2=>'Needs Improvement',1=>'Unsatisfactory'] as $r=>$label)
        <div class="flex items-center gap-3 mb-3">
            <div class="flex gap-0.5 flex-shrink-0">
                @for($i=1;$i<=5;$i++)<div class="w-2.5 h-2.5 rounded-full {{ $i<=$r ? 'bg-amber-400':'bg-gray-200' }}"></div>@endfor
            </div>
            <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full lmt-gradient-bg rounded-full"
                     style="width:{{ $maxRating > 0 ? round(($ratingDist[$r]??0)/$maxRating*100) : 0 }}%"></div>
            </div>
            <span class="text-xs font-bold text-gray-700 w-4 text-right">{{ $ratingDist[$r] ?? 0 }}</span>
        </div>
        @endforeach
    </div>

    {{-- By department --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100"><h3 class="font-black text-gray-900">By Department</h3></div>
        <table class="lmt-table">
            <thead><tr><th>Department</th><th class="text-center">Reviews</th><th class="text-center">Avg Rating</th></tr></thead>
            <tbody>
                @foreach($byDept as $d)
                <tr>
                    <td class="text-sm font-semibold text-gray-900">{{ $d['dept'] }}</td>
                    <td class="text-center text-sm text-gray-700">{{ $d['count'] }}</td>
                    <td class="text-center">
                        <span class="text-sm font-black {{ $d['avg'] >= 4 ? 'text-emerald-600' : ($d['avg'] >= 3 ? 'text-amber-600' : 'text-red-500') }}">
                            {{ $d['avg'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================
     RECRUITMENT REPORT
=================================================== --}}
@elseif($report === 'recruitment')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Job Postings',   count($jobs),                         'briefcase',   'bg-brand-50',  'text-brand-600'],
        ['Applicants',     count($candidates),                   'users',       'bg-purple-50', 'text-purple-600'],
        ['Hired',          $funnel['hired'] ?? 0,                'user-check',  'bg-emerald-50','text-emerald-600'],
        ['Hire Rate',      $hireRate.'%',                        'percent',     'bg-amber-50',  'text-amber-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Funnel --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-5">Hiring Funnel</h3>
        @php
        $funnelColors = ['applied'=>'#94A3B8','screening'=>'#3B82F6','interview'=>'#6C7DF7','assessment'=>'#8B5CF6','offer'=>'#F59E0B','hired'=>'#10B981','rejected'=>'#EF4444'];
        $funnelMax = max(array_values($funnel->toArray())) ?: 1;
        @endphp
        @foreach($funnel as $stage => $count)
        <div class="flex items-center gap-3 mb-2.5">
            <span class="text-xs font-semibold text-gray-600 w-20 capitalize">{{ $stage }}</span>
            <div class="flex-1 h-6 bg-gray-100 rounded-lg overflow-hidden relative">
                <div class="h-full rounded-lg flex items-center pl-3 transition-all"
                     style="width:{{ $funnelMax > 0 ? max(8,round($count/$funnelMax*100)) : 8 }}%; background:{{ $funnelColors[$stage] ?? '#94A3B8' }}">
                    <span class="text-white text-xs font-bold">{{ $count }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- By source --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">Applications by Source</h3>
        @if($bySource->isNotEmpty())
        @php $maxSrc = $bySource->max('count') ?: 1; @endphp
        <div class="space-y-3">
            @foreach($bySource as $src)
            <div class="flex items-center gap-3">
                <span class="text-xs font-medium text-gray-700 w-24 truncate">{{ $src['source'] }}</span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full lmt-gradient-bg rounded-full"
                         style="width:{{ round($src['count']/$maxSrc*100) }}%"></div>
                </div>
                <span class="text-xs font-black text-gray-900 w-6 text-right">{{ $src['count'] }}</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-800 text-center py-8">No source data available.</p>
        @endif
    </div>
</div>

{{-- ===================================================
     EXPENSES REPORT
=================================================== --}}
@elseif($report === 'expenses')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Approved',  '$'.number_format($totals['total'],0),    'receipt',     'bg-brand-50',  'text-brand-600'],
        ['No. of Claims',   $totals['count'],                         'list',        'bg-purple-50', 'text-purple-600'],
        ['Average Claim',   '$'.number_format($totals['avg'],0),      'bar-chart-2', 'bg-amber-50',  'text-amber-600'],
        ['Billable',        '$'.number_format($totals['billable'],0),  'dollar-sign', 'bg-emerald-50','text-emerald-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- By category --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">By Category</h3>
        @php $maxExp = $byCategory->max('total') ?: 1; @endphp
        <div class="space-y-3">
            @foreach($byCategory as $cat)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="font-medium text-gray-700">{{ $cat['name'] }}</span>
                    <span class="font-black text-gray-900">${{ number_format($cat['total'],0) }} ({{ $cat['count'] }})</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full"
                         style="width:{{ round($cat['total']/$maxExp*100) }}%; background:{{ $cat['color'] }}"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- By department --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100"><h3 class="font-black text-gray-900">By Department</h3></div>
        <table class="lmt-table">
            <thead><tr><th>Department</th><th class="text-center">Claims</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @foreach($byDept as $d)
                <tr>
                    <td class="text-sm font-semibold text-gray-900">{{ $d['dept'] }}</td>
                    <td class="text-center text-sm text-gray-600">{{ $d['count'] }}</td>
                    <td class="text-right text-sm font-bold text-brand-600">${{ number_format($d['total'],0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================
     TRAINING REPORT
=================================================== --}}
@elseif($report === 'training')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Enrollments',     $summary['total'],                              'book-open',    'bg-brand-50',  'text-brand-600'],
        ['Completed',       $summary['completed'],                          'check-circle', 'bg-emerald-50','text-emerald-600'],
        ['Completion Rate', $summary['completion_rate'].'%',               'percent',      'bg-amber-50',  'text-amber-600'],
        ['Avg Score',       $summary['avg_score'] ? $summary['avg_score'].'%' : '—', 'star','bg-purple-50','text-purple-600'],
    ] as [$label,$value,$icon,$bg,$text])
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $bg }} {{ $text }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $label }}</p>
            <p class="text-xl font-black text-gray-900">{{ $value }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- By training --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100"><h3 class="font-black text-gray-900">By Course</h3></div>
        <table class="lmt-table">
            <thead><tr><th>Course</th><th class="text-center">Enrolled</th><th class="text-center">Completed</th><th class="text-center">Avg Score</th></tr></thead>
            <tbody>
                @forelse($byTraining as $t)
                <tr>
                    <td class="text-sm font-semibold text-gray-900 max-w-48 truncate">{{ $t['title'] }}</td>
                    <td class="text-center text-sm text-gray-700">{{ $t['enrolled'] }}</td>
                    <td class="text-center text-sm font-bold text-emerald-600">{{ $t['completed'] }}</td>
                    <td class="text-center text-sm text-gray-700">{{ $t['avg_score'] ? $t['avg_score'].'%' : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-8 text-gray-800">No training data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- By department --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100"><h3 class="font-black text-gray-900">By Department</h3></div>
        <table class="lmt-table">
            <thead><tr><th>Department</th><th class="text-center">Enrolled</th><th class="text-center">Completed</th></tr></thead>
            <tbody>
                @forelse($byDept as $d)
                <tr>
                    <td class="text-sm font-semibold text-gray-900">{{ $d['dept'] }}</td>
                    <td class="text-center text-sm text-gray-700">{{ $d['enrolled'] }}</td>
                    <td class="text-center text-sm font-bold text-emerald-600">{{ $d['completed'] }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-8 text-gray-800">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush