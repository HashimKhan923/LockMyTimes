@extends('layouts.admin')
@section('title','Attendance')
@section('page-title','Attendance')

@section('content')

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Attendance</h2>
        <p class="text-sm text-gray-800 mt-0.5">{{ $month->format('F Y') }}</p>
    </div>
    <button onclick="document.getElementById('manual-modal').classList.remove('hidden');document.getElementById('manual-modal').classList.add('flex');"
            class="lmt-btn-secondary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Manual Entry
    </button>
</div>

{{-- Toolbar — month nav + view switcher --}}
<div class="lmt-card mb-6">
    <div class="flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.attendance.index', [$tenant, 'view' => 'calendar', 'month' => $month->copy()->subMonth()->format('Y-m')]) }}"
               class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors">
                <i data-lucide="chevron-left" class="w-4 h-4 text-gray-800"></i>
            </a>
            <div class="text-base lg:text-lg font-black text-gray-900 min-w-[170px] text-center">
                {{ $month->format('F Y') }}
            </div>
            <a href="{{ route('admin.attendance.index', [$tenant, 'view' => 'calendar', 'month' => $month->copy()->addMonth()->format('Y-m')]) }}"
               class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors">
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-800"></i>
            </a>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <form method="GET" action="{{ route('admin.attendance.index', $tenant) }}" class="flex items-center gap-2">
                <input type="hidden" name="view" value="calendar"/>
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}"/>
                <select name="employee" class="lmt-select py-2 text-sm w-auto min-w-40" onchange="this.form.submit()">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
                <select name="department" class="lmt-select py-2 text-sm w-auto min-w-40" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </form>

            <div class="inline-flex p-1 bg-gray-100 rounded-xl text-xs font-bold">
                <a href="{{ route('admin.attendance.index', [$tenant, 'view' => 'calendar', 'month' => $month->format('Y-m')]) }}"
                   class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition bg-white shadow text-gray-900">
                    <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i> Calendar
                </a>
                <a href="{{ route('admin.attendance.index', $tenant) }}"
                   class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition text-gray-800">
                    <i data-lucide="list" class="w-3.5 h-3.5"></i> List
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Calendar grid — one cell per day, showing Present/Absent/Late counts --}}
<div class="lmt-card">
    <div class="grid grid-cols-7 gap-2 mb-2">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
        <div class="text-center text-[10px] font-black uppercase tracking-wider text-gray-800 py-2">{{ $d }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-2">
        @foreach($calendarCells as $cell)
        @php
            $hasData = $cell->present > 0 || $cell->absent > 0 || $cell->late > 0 || $cell->on_leave > 0;
            $rate = $totalActive > 0 && $hasData ? round(($cell->present / $totalActive) * 100) : null;
            $cellHref = route('admin.attendance.index', [$tenant, 'date' => $cell->date->toDateString()])
                . (request('employee') ? '&employee='.request('employee') : '')
                . (request('department') ? '&department='.request('department') : '');
        @endphp
        <a href="{{ $cellHref }}"
           class="relative rounded-xl p-2 text-left transition-all hover:shadow-soft min-h-20 flex flex-col
                  {{ $cell->in_month ? 'bg-white border border-gray-100' : 'bg-gray-50/50 border border-transparent opacity-40 pointer-events-none' }}
                  {{ $cell->is_today ? 'ring-2 ring-brand-500' : '' }}">
            <span class="text-sm font-bold {{ $cell->is_today ? 'text-brand-600' : 'text-gray-900' }}">{{ $cell->date->day }}</span>

            @if($cell->in_month && !$cell->is_future)
                @if($cell->is_weekend && !$hasData)
                    <span class="text-[10px] text-gray-800 mt-auto">Weekend</span>
                @elseif($hasData)
                    <div class="mt-auto space-y-0.5">
                        <div class="flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $cell->present }} present
                        </div>
                        @if($cell->late > 0)
                        <div class="flex items-center gap-1 text-[10px] font-bold text-amber-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ $cell->late }} late
                        </div>
                        @endif
                        @if($cell->absent > 0)
                        <div class="flex items-center gap-1 text-[10px] font-bold text-red-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ $cell->absent }} absent
                        </div>
                        @endif
                        @if($cell->on_leave > 0)
                        <div class="flex items-center gap-1 text-[10px] font-bold text-brand-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>{{ $cell->on_leave }} on leave
                        </div>
                        @endif
                    </div>
                @endif
            @endif
        </a>
        @endforeach
    </div>

    <div class="flex items-center justify-center gap-4 mt-5 flex-wrap text-[11px] text-gray-800">
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Present</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Late</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>Absent</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-500"></span>On Leave</span>
        <span class="text-gray-800">· Click a day to see the full list for that date</span>
    </div>
</div>

@include('admin.attendance._manual_modal')

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush
