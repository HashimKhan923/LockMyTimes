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
            <div class="flex flex-col sm:items-end gap-2">
                <form method="GET" action="{{ route('admin.attendance.employee-sheet', [$tenant, $employee->id]) }}">
                    <input type="month" name="month" value="{{ $month }}"
                           class="lmt-input w-auto" onchange="this.form.submit()"/>
                </form>
                <form method="GET" action="{{ route('admin.attendance.employee-sheet', [$tenant, $employee->id]) }}"
                      class="flex items-center gap-2">
                    <span class="text-xs text-gray-800 font-semibold">or custom range:</span>
                    <input type="date" name="from" value="{{ request('from') }}" title="From date" class="lmt-input py-1.5 text-sm w-auto"/>
                    <input type="date" name="to" value="{{ request('to') }}" title="To date" class="lmt-input py-1.5 text-sm w-auto"/>
                    <button type="submit" class="lmt-btn-primary lmt-btn-sm">Go</button>
                    @if($isCustomRange)
                    <a href="{{ route('admin.attendance.employee-sheet', [$tenant, $employee->id]) }}" class="lmt-btn-ghost lmt-btn-sm">Back to month view</a>
                    @endif
                </form>
            </div>
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

    @if($isCustomRange)
    {{-- Custom range — a day-by-day table, since an arbitrary range (e.g. 10
         days, or spanning two months) doesn't fit a single-month calendar grid. --}}
    <div class="lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-black text-gray-900">{{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}</h3>
            <span class="lmt-badge-gray text-xs">{{ $start->diffInDays($end) + 1 }} days</span>
        </div>
        <div class="overflow-x-auto">
            <table class="lmt-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Hours</th>
                        <th>Overtime</th>
                    </tr>
                </thead>
                <tbody>
                    @php $cursor = $start->copy(); @endphp
                    @while($cursor->lte($end))
                    @php
                        $dateKey = $cursor->format('Y-m-d');
                        $rec = $records->get($dateKey);
                        $isWeekend = in_array($cursor->dayOfWeek, [0,6]);
                    @endphp
                    <tr>
                        <td class="text-sm font-semibold text-gray-900">
                            {{ $cursor->format('D, M j') }}
                            @if($cursor->isToday())<span class="lmt-badge-brand text-xs ml-1">Today</span>@endif
                        </td>
                        <td>
                            @if($rec)
                                @php
                                $sc = ['present' => $rec->is_late ? 'lmt-badge-amber' : 'lmt-badge-green', 'absent' => 'lmt-badge-red', 'on_leave' => 'lmt-badge-brand', 'holiday' => 'lmt-badge-gray'];
                                @endphp
                                <span class="{{ $sc[$rec->status] ?? 'lmt-badge-gray' }} text-xs">{{ $rec->is_late ? 'Late' : ucfirst(str_replace('_',' ',$rec->status)) }}</span>
                            @elseif($isWeekend)
                                <span class="text-xs text-gray-800">Weekend</span>
                            @elseif($dateKey < now()->toDateString())
                                <span class="lmt-badge-red text-xs">Absent</span>
                            @else
                                <span class="text-xs text-gray-800">—</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-800">{{ $rec?->clock_in_at?->format('h:i A') ?? '—' }}</td>
                        <td class="text-sm text-gray-800">{{ $rec?->clock_out_at?->format('h:i A') ?? '—' }}</td>
                        <td class="text-sm font-semibold text-gray-900">{{ format_hours($rec?->total_hours) }}</td>
                        <td class="text-sm text-amber-600">{{ $rec?->overtime_hours > 0 ? format_hours($rec->overtime_hours) : '—' }}</td>
                    </tr>
                    @php $cursor->addDay(); @endphp
                    @endwhile
                </tbody>
            </table>
        </div>
    </div>
    @else
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
    @endif
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush