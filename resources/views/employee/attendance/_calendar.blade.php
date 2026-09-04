<div class="lmt-card" data-lmt-anim="fade-up">

    {{-- Weekday header --}}
    @php
        $weekDays = $weekStartsMon
            ? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']
            : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    @endphp
    <div class="grid grid-cols-7 gap-2 mb-2">
        @foreach($weekDays as $d)
            <div class="text-center text-[10px] font-black uppercase tracking-wider text-gray-800 py-2">{{ $d }}</div>
        @endforeach
    </div>

    {{-- Day cells --}}
    <div class="grid grid-cols-7 gap-2">
        @foreach($calendarCells as $cell)
            @php
                $statusKey = $cell->status_key;
                $att       = $cell->attendance;
                $hol       = $cell->holiday;
                $key       = $cell->date->toDateString();
            @endphp
            <button type="button"
                    @click="openDay(@js($key))"
                    class="cal-cell relative rounded-xl p-2 text-left transition-all hover:shadow-soft
                           {{ $cell->in_month ? 'bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700' : 'bg-gray-50/50 dark:bg-slate-900 border border-transparent opacity-50' }}
                           {{ $cell->is_today ? 'ring-2' : '' }}"
                    @if($cell->is_today) style="--tw-ring-color: var(--brand-500);" @endif>

                <div class="flex items-start justify-between">
                    <span class="text-sm font-bold {{ $cell->is_today ? '' : 'text-gray-900 dark:text-slate-100' }}"
                          @if($cell->is_today) style="color:var(--brand-600);" @endif>
                        {{ $cell->date->day }}
                    </span>
                    @if($att?->is_late)
                        <span class="text-[9px] font-black text-amber-600 bg-amber-50 dark:bg-amber-500/10 px-1 rounded uppercase">L</span>
                    @endif
                </div>

                {{-- Hours --}}
                @if($att && $att->total_hours > 0)
                    <div class="mt-1.5 font-mono text-[11px] font-bold text-gray-800 dark:text-slate-200">
                        {{ format_hours($att->total_hours) }}
                    </div>
                @endif

                {{-- Status dot --}}
                <div class="absolute bottom-2 right-2 w-2.5 h-2.5 rounded-full st-{{ $statusKey }}"></div>

                @if($hol && $cell->in_month)
                    <p class="text-[9px] text-cyan-600 font-bold mt-1 truncate">{{ $hol->name }}</p>
                @elseif($att?->status === 'on_leave' && $cell->in_month)
                    <p class="text-[9px] text-violet-600 font-bold mt-1">On leave</p>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Legend --}}
    <div class="flex items-center justify-center gap-3 mt-5 flex-wrap text-[11px] text-gray-800">
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full st-present"></span>Present</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full st-late"></span>Late</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full st-absent"></span>Absent</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full st-leave"></span>On leave</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full st-half"></span>Half-day</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full st-holiday"></span>Holiday</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full st-weekend"></span>Weekend</span>
    </div>
</div>