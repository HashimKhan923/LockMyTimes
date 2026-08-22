@extends('layouts.admin')
@section('title', $employee->full_name . ' — Schedule')
@section('page-title', 'Employee Schedule')

@section('content')

<div class="max-w-4xl mx-auto">

    <a href="{{ route('admin.shifts.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-700 mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Shifts
    </a>

    <div class="lmt-card mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl lmt-gradient-bg flex items-center justify-center text-white text-xl font-black">
                    {{ substr($employee->first_name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-900">{{ $employee->full_name }}</h2>
                    <p class="text-sm text-gray-800">{{ $employee->position?->title }} · {{ $employee->department?->name }}</p>
                </div>
            </div>
            <form method="GET">
                <input type="month" name="month" value="{{ $month }}"
                       class="lmt-input w-auto" onchange="this.form.submit()"/>
            </form>
        </div>
    </div>

    {{-- Current assignments --}}
    <div class="lmt-card mb-6">
        <h3 class="font-black text-gray-900 mb-4">Active Shift Assignments</h3>
        @forelse($assignments as $assignment)
        <div class="flex items-center gap-4 py-3 border-b border-gray-50 last:border-none">
            <div class="w-3 h-3 rounded-full flex-shrink-0" style="background:{{ $assignment->shift->color ?? '#6C7DF7' }}"></div>
            <div class="flex-1">
                <p class="font-bold text-gray-900 text-sm">{{ $assignment->shift->name }}</p>
                <p class="text-xs text-gray-800">
                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $assignment->shift->start_time)->format('h:i A') }}
                    –
                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $assignment->shift->end_time)->format('h:i A') }}
                    · {{ $assignment->shift->total_hours }}h/day
                </p>
            </div>
            <div class="text-right text-xs text-gray-800">
                <p>From: {{ \Carbon\Carbon::parse($assignment->start_date)->format('M j, Y') }}</p>
                <p>{{ $assignment->end_date ? 'To: '.\Carbon\Carbon::parse($assignment->end_date)->format('M j, Y') : 'Ongoing' }}</p>
            </div>
            <form action="{{ route('admin.shifts.unassign', [$tenant, $assignment->id]) }}" method="POST"
                  onsubmit="return confirm('Remove this shift assignment?')">
                @csrf @method('PATCH')
                <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </form>
        </div>
        @empty
        <div class="text-center py-8">
            <i data-lucide="calendar-off" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
            <p class="text-sm text-gray-800">No shift assignments in this period</p>
        </div>
        @endforelse
    </div>

</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush