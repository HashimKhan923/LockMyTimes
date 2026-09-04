@extends('layouts.admin')
@section('title', $employee->full_name . ' — Timesheet')
@section('page-title','Employee Timesheet')

@section('content')

<div class="max-w-4xl mx-auto">

    <a href="{{ route('admin.attendance.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Attendance
    </a>

    {{-- Employee header + month picker --}}
    <div class="lmt-card mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl lmt-gradient-bg flex items-center justify-center text-white text-xl font-black">
                    {{ substr($employee->first_name,0,1) }}
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-900">{{ $employee->full_name }}</h2>
                    <p class="text-sm text-gray-800">{{ $employee->position?->title }} · {{ $employee->department?->name }}</p>
                </div>
            </div>
            <form method="GET" action="{{ route('admin.attendance.employee-sheet', [$tenant, $employee->id]) }}">
                <input type="month" name="month" value="{{ $month }}"
                       class="lmt-input w-auto" onchange="this.form.submit()"/>
            </form>
        </div>

        {{-- Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-5 pt-5 border-t border-gray-100">
            @foreach([
                ['label'=>'Days Present','value'=>$summary['present'],'color'=>'text-emerald-600'],
                ['label'=>'Total Days','value'=>$summary['total_days'],'color'=>'text-gray-900'],
                ['label'=>'Late Days','value'=>$summary['late'],'color'=>'text-amber-600'],
                ['label'=>'Total Hours','value'=>format_hours($summary['total_hours'], '0h'),'color'=>'text-brand-600'],
                ['label'=>'Overtime','value'=>format_hours($summary['overtime_hours'], '0h'),'color'=>'text-purple-600'],
            ] as $s)
            <div class="text-center">
                <p class="text-2xl font-black {{ $s['color'] }}">{{ $s['value'] }}</p>
                <p class="text-xs text-gray-800 mt-0.5">{{ $s['label'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Calendar grid --}}
    <div class="lmt-card">
        {{-- Day headers --}}
        <div class="grid grid-cols-7 mb-2">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
            <div class="text-center text-xs font-bold text-gray-800 py-2">{{ $day }}</div>
            @endforeach
        </div>

        {{-- Calendar days --}}
        @php
        $firstDay   = $start->copy()->startOfMonth();
        $startBlank = $firstDay->dayOfWeek; // 0=Sun
        $daysInMonth= $start->daysInMonth;
        @endphp

        <div class="grid grid-cols-7 gap-1">
            {{-- Leading blanks --}}
            @for($i = 0; $i < $startBlank; $i++)
            <div></div>
            @endfor

            @for($d = 1; $d <= $daysInMonth; $d++)
            @php
            $dateKey = $start->copy()->setDay($d)->format('Y-m-d');
            $rec     = $records->get($dateKey);
            $isToday = $dateKey === now()->toDateString();
            $isWeekend = in_array(Carbon\Carbon::parse($dateKey)->dayOfWeek, [0,6]);

            $cellBg = 'bg-gray-50';
            $dotColor = '';
            if ($rec) {
                $cellBg = match($rec->status) {
                    'present'  => $rec->is_late ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200',
                    'absent'   => 'bg-red-50 border border-red-200',
                    'on_leave' => 'bg-brand-50 border border-brand-200',
                    'holiday'  => 'bg-gray-100',
                    default    => 'bg-gray-50',
                };
            } elseif($isWeekend) {
                $cellBg = 'bg-gray-100/50';
            }
            @endphp
            <div class="rounded-xl p-2 text-center {{ $cellBg }} {{ $isToday ? 'ring-2 ring-brand-500' : '' }} min-h-16 flex flex-col">
                <span class="text-xs font-bold {{ $isToday ? 'text-brand-600' : 'text-gray-800' }}">{{ $d }}</span>
                @if($rec)
                <span class="text-[10px] font-bold mt-auto {{ $rec->status === 'present' ? ($rec->is_late ? 'text-amber-600' : 'text-emerald-600') : ($rec->status === 'absent' ? 'text-red-500' : 'text-brand-600') }}">
                    {{ $rec->clock_in_at?->format('h:ia') ?? strtoupper(substr($rec->status,0,3)) }}
                </span>
                @if($rec->total_hours > 0)
                <span class="text-[10px] text-gray-800">{{ format_hours($rec->total_hours) }}</span>
                @endif
                @elseif(!$isWeekend && $dateKey < now()->toDateString())
                <span class="text-[10px] text-red-600 mt-auto">Absent</span>
                @endif
            </div>
            @endfor
        </div>

        {{-- Legend --}}
        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-4 text-xs">
            @foreach([
                ['color'=>'bg-emerald-50 border border-emerald-200','label'=>'Present'],
                ['color'=>'bg-amber-50 border border-amber-200','label'=>'Present (Late)'],
                ['color'=>'bg-red-50 border border-red-200','label'=>'Absent'],
                ['color'=>'bg-brand-50 border border-brand-200','label'=>'On Leave'],
                ['color'=>'bg-gray-100','label'=>'Weekend/Holiday'],
            ] as $leg)
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded {{ $leg['color'] }}"></div>
                <span class="text-gray-800">{{ $leg['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush