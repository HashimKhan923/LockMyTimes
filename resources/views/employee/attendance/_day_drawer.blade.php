<div class="p-6 pt-12">

    {{-- Header --}}
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-800">{{ $date->format('l') }}</p>
        <h2 class="text-2xl font-black text-gray-900 dark:text-slate-100 mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
            {{ $date->format('F j, Y') }}
        </h2>

        @if($att)
            @php
                [$lbl, $cls] = match($att->status) {
                    'present'  => $att->is_late ? ['Late','lmt-badge-amber'] : ['Present','lmt-badge-green'],
                    'absent'   => ['Absent', 'lmt-badge-red'],
                    'on_leave' => ['On Leave','lmt-badge-brand'],
                    'half_day' => ['Half Day','lmt-badge-amber'],
                    'holiday'  => ['Holiday', 'lmt-badge-brand'],
                    default    => [ucfirst(str_replace('_', ' ', $att->status)),'lmt-badge-gray'],
                };
            @endphp
            <div class="flex flex-wrap gap-2 mt-3">
                <span class="{{ $cls }}">{{ $lbl }}</span>
                @if($att->is_late)
                    <span class="lmt-badge-amber">
                        <i data-lucide="clock" class="w-3 h-3"></i>
                        {{ $att->late_minutes }} min late
                    </span>
                @endif
                @if($att->is_early_out)
                    <span class="lmt-badge-amber">
                        <i data-lucide="log-out" class="w-3 h-3"></i>
                        Left {{ $att->early_minutes }} min early
                    </span>
                @endif
                @if($att->is_geofence_breach)
                    <span class="lmt-badge-red">
                        <i data-lucide="map-pin-off" class="w-3 h-3"></i>
                        Off-site
                    </span>
                @endif
                @if($att->is_manual_entry)
                    <span class="lmt-badge-gray">
                        <i data-lucide="edit-3" class="w-3 h-3"></i>
                        Manual
                    </span>
                @endif
            </div>
        @elseif($holiday)
            <div class="mt-3">
                <span class="lmt-badge-brand">
                    <i data-lucide="party-popper" class="w-3 h-3"></i>
                    {{ $holiday->name }}
                </span>
            </div>
        @elseif($date->isWeekend())
            <div class="mt-3"><span class="lmt-badge-gray">Weekend</span></div>
        @elseif($date->isFuture())
            <div class="mt-3"><span class="lmt-badge-gray">Future date</span></div>
        @else
            <div class="mt-3"><span class="lmt-badge-gray">No record</span></div>
        @endif
    </div>

    {{-- Empty state --}}
    @if(! $att && ! $holiday)
        <div class="text-center py-12 border border-dashed border-gray-200 dark:border-slate-700 rounded-2xl">
            <i data-lucide="moon" class="w-10 h-10 mx-auto text-gray-800 mb-3"></i>
            <p class="text-sm text-gray-800">No attendance recorded for this day.</p>
        </div>
    @endif

    @if($att)
        {{-- Key facts grid --}}
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-slate-800">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-800">Clock In</p>
                <p class="font-mono text-lg font-black text-gray-900 dark:text-slate-100 mt-1">
                    {{ $att->clock_in_at ? \Carbon\Carbon::parse($att->clock_in_at)->format('h:i A') : '—' }}
                </p>
                @if($att->source)
                    <p class="text-[10px] text-gray-800 mt-1 uppercase tracking-wide">via {{ $att->source }}</p>
                @endif
            </div>
            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-slate-800">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-800">Clock Out</p>
                <p class="font-mono text-lg font-black text-gray-900 dark:text-slate-100 mt-1">
                    {{ $att->clock_out_at ? \Carbon\Carbon::parse($att->clock_out_at)->format('h:i A') : '—' }}
                </p>
            </div>
            <div class="p-4 rounded-2xl bg-brand-50 dark:bg-slate-800" style="background:var(--brand-50);">
                <p class="text-[10px] font-bold uppercase tracking-wider" style="color:var(--brand-700);">Total Hours</p>
                <p class="font-mono text-lg font-black mt-1" style="color:var(--brand-700);">
                    {{ number_format((float) ($att->total_hours ?? 0), 2) }}h
                </p>
                <p class="text-[10px] mt-1 uppercase tracking-wide" style="color:var(--brand-700);">
                    Regular {{ number_format((float) ($att->regular_hours ?? 0), 1) }}h
                    @if($att->overtime_hours > 0) · OT +{{ number_format((float) $att->overtime_hours, 1) }}h @endif
                </p>
            </div>
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10">
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Break Time</p>
                <p class="font-mono text-lg font-black text-amber-700 mt-1">
                    {{ number_format((float) ($att->break_hours ?? 0), 2) }}h
                </p>
                <p class="text-[10px] text-amber-700 mt-1 uppercase tracking-wide">
                    {{ $att->breaks->count() }} session{{ $att->breaks->count() === 1 ? '' : 's' }}
                </p>
            </div>
        </div>

        {{-- Shift --}}
        @if($shift)
            <div class="mb-6 p-4 rounded-2xl border border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="moon" class="w-4 h-4" style="color:var(--brand-500);"></i>
                    <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">Scheduled Shift</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-slate-300">{{ $shift->name }} · {{ $shift->label }}</p>
            </div>
        @endif

        {{-- Location --}}
        @if($att->location)
            <div class="mb-6 p-4 rounded-2xl border border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="map-pin" class="w-4 h-4" style="color:var(--brand-500);"></i>
                    <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">Location</h3>
                </div>
                <p class="text-sm font-bold text-gray-900 dark:text-slate-100">{{ $att->location->name }}</p>
                @if($att->location->address)
                    <p class="text-xs text-gray-800 mt-0.5">{{ $att->location->address }}</p>
                @endif
                @if($att->clock_in_distance_meters !== null)
                    <p class="text-xs text-gray-800 mt-2 font-mono">
                        Distance at check-in: {{ (int) $att->clock_in_distance_meters }}m
                    </p>
                @endif
            </div>
        @endif

        {{-- Breaks timeline --}}
        @if($att->breaks->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-sm font-black text-gray-900 dark:text-slate-100 mb-3 flex items-center gap-2">
                    <i data-lucide="coffee" class="w-4 h-4" style="color:var(--brand-500);"></i>
                    Breaks ({{ $att->breaks->count() }})
                </h3>
                <div class="space-y-2">
                    @foreach($att->breaks as $b)
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-slate-800">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-500/15 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="{{ ['lunch'=>'utensils','tea'=>'coffee','personal'=>'user','other'=>'more-horizontal'][$b->break_type ?? 'other'] ?? 'coffee' }}" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-slate-100 capitalize">{{ $b->break_type ?? 'Break' }}</p>
                                <p class="text-xs text-gray-800 font-mono">
                                    {{ \Carbon\Carbon::parse($b->start_at)->format('h:i A') }}
                                    @if($b->end_at) — {{ \Carbon\Carbon::parse($b->end_at)->format('h:i A') }} @else — ongoing @endif
                                </p>
                            </div>
                            <span class="font-mono text-xs font-bold text-gray-600 dark:text-slate-300">
                                {{ $b->end_at ? (int) $b->duration_minutes . ' min' : '…' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if($att->notes)
            <div class="p-4 rounded-2xl border border-gray-100 dark:border-slate-700">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 mb-2">Notes</h3>
                <p class="text-sm text-gray-700 dark:text-slate-200 whitespace-pre-line">{{ $att->notes }}</p>
            </div>
        @endif
    @endif
</div>