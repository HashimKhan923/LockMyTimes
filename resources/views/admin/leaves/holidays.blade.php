@extends('layouts.admin')
@section('title','Holidays')
@section('page-title','Holiday Calendar')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Holiday Calendar</h2>
        <p class="text-sm text-gray-800 mt-0.5">{{ $holidays->count() }} holidays in {{ $year }}</p>
    </div>
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-2">
            <select name="year" class="lmt-select py-2 text-sm w-auto" onchange="this.form.submit()">
                @foreach([now()->year - 1, now()->year, now()->year + 1] as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('admin.leaves.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back
        </a>
        <button onclick="document.getElementById('add-holiday-modal').classList.remove('hidden');document.getElementById('add-holiday-modal').classList.add('flex');"
                class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Holiday
        </button>
    </div>
</div>

@php
$typeColors = [
    'federal'   => 'lmt-badge-brand',
    'state'     => 'lmt-badge-green',
    'company'   => 'lmt-badge-amber',
    'religious' => 'lmt-badge-red',
    'optional'  => 'lmt-badge-gray',
];
@endphp

<div class="lmt-card p-0 overflow-hidden">
    <table class="lmt-table">
        <thead>
            <tr>
                <th>Holiday</th>
                <th>Date</th>
                <th>Day</th>
                <th>Type</th>
                <th>Paid</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($holidays as $holiday)
            @php $isPast = $holiday->date->isPast(); @endphp
            <tr class="{{ $isPast ? 'opacity-60' : '' }}">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl lmt-gradient-bg flex flex-col items-center justify-center text-white">
                            <span class="text-[10px] font-semibold leading-none">{{ $holiday->date->format('M') }}</span>
                            <span class="text-base font-black leading-tight">{{ $holiday->date->format('d') }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $holiday->name }}</p>
                            @if($holiday->description)
                            <p class="text-xs text-gray-800">{{ $holiday->description }}</p>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-800">{{ $holiday->date->format('F j, Y') }}</td>
                <td class="text-sm text-gray-800">{{ $holiday->date->format('l') }}</td>
                <td>
                    <span class="{{ $typeColors[$holiday->type] ?? 'lmt-badge-gray' }} text-xs capitalize">
                        {{ $holiday->type }}
                    </span>
                </td>
                <td>
                    <span class="{{ $holiday->is_paid ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                        {{ $holiday->is_paid ? 'Paid' : 'Unpaid' }}
                    </span>
                </td>
                <td>
                    @if($holiday->date->isToday())
                    <span class="lmt-badge-brand text-xs">Today</span>
                    @elseif(!$isPast)
                    <span class="text-xs text-gray-800">In {{ today()->diffInDays($holiday->date) }} days</span>
                    @else
                    <span class="text-xs text-gray-800">Past</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('admin.leaves.holidays.destroy', [$tenant, $holiday->id]) }}"
                          method="POST"
                          onsubmit="return confirm('Remove this holiday?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-14">
                    <i data-lucide="calendar" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                    <p class="text-gray-800">No holidays added for {{ $year }}</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Add Holiday Modal --}}
<div id="add-holiday-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Holiday</h3>
        <form action="{{ route('admin.leaves.holidays.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Holiday Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="lmt-input" placeholder="e.g. Independence Day"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        @foreach(['federal'=>'Federal','state'=>'State','company'=>'Company','religious'=>'Religious','optional'=>'Optional'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <input type="text" name="description" class="lmt-input" placeholder="Optional description…"/>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_paid" value="1" checked class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-800">Paid Holiday</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Add Holiday</button>
                <button type="button"
                        onclick="document.getElementById('add-holiday-modal').classList.add('hidden');document.getElementById('add-holiday-modal').classList.remove('flex');"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush