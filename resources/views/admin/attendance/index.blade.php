@extends('layouts.admin')
@section('title','Attendance')
@section('page-title','Attendance')

@section('content')

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Attendance</h2>
        <p class="text-sm text-gray-400 mt-0.5">{{ $date->format('l, F j, Y') }}</p>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        {{-- Date picker --}}
        <form action="{{ route('admin.attendance.index', $tenant) }}" method="GET" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date->toDateString() }}"
                   class="lmt-input py-2 text-sm w-auto"
                   onchange="this.form.submit()"/>
        </form>
        <button onclick="document.getElementById('manual-modal').classList.remove('hidden');document.getElementById('manual-modal').classList.add('flex');"
                class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Manual Entry
        </button>
        @include('exports.buttons', [
            'route'  => 'admin.attendance.export',
            'params' => [$tenant],
            'extra'  => ['from' => $date->copy()->startOfMonth()->toDateString(), 'to' => $date->copy()->endOfMonth()->toDateString()]
        ])
    </div>
</div>

{{-- Today's KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label'=>'Total Staff','value'=>$totalActive,'icon'=>'users','bg'=>'bg-gray-100','text'=>'text-gray-600'],
        ['label'=>'Present','value'=>$presentCount,'icon'=>'user-check','bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Absent','value'=>$absentCount,'icon'=>'user-x','bg'=>'bg-red-50','text'=>'text-red-600'],
        ['label'=>'Late','value'=>$lateCount,'icon'=>'clock','bg'=>'bg-amber-50','text'=>'text-amber-600'],
        ['label'=>'On Leave','value'=>$onLeaveCount,'icon'=>'calendar-off','bg'=>'bg-brand-50','text'=>'text-brand-600'],
    ] as $s)
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} {{ $s['text'] }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400">{{ $s['label'] }}</p>
            <p class="text-xl font-black text-gray-900">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Attendance rate bar --}}
@php $rate = $totalActive > 0 ? round(($presentCount / $totalActive) * 100) : 0; @endphp
<div class="lmt-card mb-6">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h3 class="font-black text-gray-900">Attendance Rate</h3>
            <p class="text-sm text-gray-400">{{ $date->format('M j, Y') }}</p>
        </div>
        <span class="text-3xl font-black {{ $rate >= 80 ? 'text-emerald-600' : ($rate >= 60 ? 'text-amber-600' : 'text-red-600') }}">
            {{ $rate }}%
        </span>
    </div>
    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
        <div class="h-full rounded-full transition-all duration-700"
             style="width:{{ $rate }}%; background:{{ $rate >= 80 ? '#10B981' : ($rate >= 60 ? '#F59E0B' : '#EF4444') }};">
        </div>
    </div>

    {{-- 30-day trend chart --}}
    <div class="mt-5">
        <p class="text-xs font-semibold text-gray-400 mb-3">Last 30 Days</p>
        <div class="flex items-end gap-0.5 h-12">
            @foreach($monthlyStats as $day)
            @php $dayRate = $totalActive > 0 ? ($day['present'] / max($totalActive,1)) * 100 : 0; @endphp
            <div class="flex-1 rounded-t transition-all duration-300 cursor-pointer"
                 title="{{ $day['date'] }}: {{ $day['present'] }} present"
                 style="height:{{ max($dayRate,4) }}%;
                        background:{{ $dayRate >= 80 ? '#10B981' : ($dayRate >= 60 ? '#F59E0B' : '#EF4444') }};
                        opacity:{{ $day['date'] === $date->toDateString() ? 1 : 0.6 }};">
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Filters + Table --}}
<div class="lmt-card p-0 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-wrap items-center gap-3">
        <form action="{{ route('admin.attendance.index', $tenant) }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
            <input type="hidden" name="date" value="{{ $date->toDateString() }}"/>
            <div class="relative flex-1 min-w-48 max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="lmt-input pl-10 py-2 text-sm" placeholder="Search employees…"/>
            </div>
            <select name="department" class="lmt-select py-2 text-sm w-auto min-w-40">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
                @endforeach
            </select>
            <select name="status" class="lmt-select py-2 text-sm w-auto">
                <option value="">All Status</option>
                @foreach(['present','absent','on_leave','half_day','holiday'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_',' ',$s)) }}
                </option>
                @endforeach
            </select>
            <button type="submit" class="lmt-btn-primary lmt-btn-sm">Filter</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Total Hours</th>
                    <th>OT Hours</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                @php
                $statusColors = ['present'=>'lmt-badge-green','absent'=>'lmt-badge-red','on_leave'=>'lmt-badge-amber','half_day'=>'lmt-badge-brand','holiday'=>'lmt-badge-gray'];
                $sourceColors = ['qr'=>'lmt-badge-brand','web'=>'lmt-badge-gray','manual'=>'lmt-badge-amber','mobile'=>'lmt-badge-green'];
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">
                                {{ substr($rec->employee->first_name ?? 'E', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">
                                    {{ $rec->employee->full_name ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $rec->employee->department?->name ?? '' }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>
                            <span class="text-sm font-semibold {{ $rec->is_late ? 'text-amber-600' : 'text-gray-900' }}">
                                {{ $rec->clock_in_at?->format('h:i A') ?? '—' }}
                            </span>
                            @if($rec->is_late)
                            <span class="block text-xs text-amber-500">+{{ $rec->late_minutes }}m late</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="text-sm {{ $rec->is_early_out ? 'text-amber-600' : 'text-gray-900' }}">
                            {{ $rec->clock_out_at?->format('h:i A') ?? '—' }}
                        </span>
                        @if($rec->is_early_out)
                        <span class="block text-xs text-amber-500">-{{ $rec->early_minutes }}m early</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-sm font-bold {{ $rec->total_hours >= 8 ? 'text-emerald-600' : 'text-gray-900' }}">
                            {{ $rec->total_hours ? number_format($rec->total_hours,1).'h' : '—' }}
                        </span>
                    </td>
                    <td>
                        @if($rec->overtime_hours > 0)
                        <span class="lmt-badge-amber text-xs">{{ number_format($rec->overtime_hours,1) }}h OT</span>
                        @else
                        <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $statusColors[$rec->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ str_replace('_',' ',$rec->status) }}
                        </span>
                        @if($rec->is_geofence_breach)
                        <span class="block lmt-badge-red text-xs mt-1">⚠ Geo breach</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $sourceColors[$rec->source] ?? 'lmt-badge-gray' }} text-xs uppercase">
                            {{ $rec->source }}
                        </span>
                        @if($rec->is_manual_entry)
                        <span class="block text-xs text-gray-400 mt-0.5">Manual</span>
                        @endif
                    </td>
                    <td class="text-xs text-gray-400 max-w-24 truncate">
                        {{ $rec->notes ?? '—' }}
                    </td>
                    <td>
                        <a href="{{ route('admin.attendance.employee-sheet', [$tenant, $rec->employee_id]) }}"
                           class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-16">
                        <i data-lucide="clock" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="font-semibold text-gray-400">No attendance records for {{ $date->format('M j, Y') }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $records->links() }}</div>
    @endif
</div>

{{-- Manual Entry Modal --}}
<div id="manual-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Manual Attendance Entry</h3>
        <form action="{{ route('admin.attendance.manual', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select Employee —</option>
                    @foreach(\App\Models\Tenant\Employee::active()->orderBy('first_name')->get() as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Date <span class="text-red-500">*</span></label>
                <input type="date" name="work_date" value="{{ $date->toDateString() }}" required class="lmt-input"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Clock In <span class="text-red-500">*</span></label>
                    <input type="time" name="clock_in_at" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Clock Out</label>
                    <input type="time" name="clock_out_at" class="lmt-input"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="lmt-select">
                    @foreach(['present'=>'Present','absent'=>'Absent','half_day'=>'Half Day','on_leave'=>'On Leave','holiday'=>'Holiday'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Notes</label>
                <input type="text" name="notes" class="lmt-input" placeholder="Reason for manual entry…"/>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Save Entry</button>
                <button type="button"
                        onclick="document.getElementById('manual-modal').classList.add('hidden');document.getElementById('manual-modal').classList.remove('flex');"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush