{{-- Today's Clock Card — shared by the Attendance page and the Dashboard.
     Expects (from the including page): $clockStatus, $shift, $rec, $activeBreak,
     $tenantTimezone. Must be rendered inside an `x-data="attendancePage()"` scope. --}}
@php
    $statusMeta = match($clockStatus) {
        'not_clocked_in' => ['label'=>'Not clocked in', 'dot'=>'#94a3b8'],
        'clocked_in'     => ['label'=>'Clocked in',     'dot'=>'#10b981'],
        'on_break'       => ['label'=>'On break',       'dot'=>'#f59e0b'],
        'clocked_out'    => ['label'=>'Day complete',   'dot'=>'#6366f1'],
        default          => ['label'=>'Not clocked in', 'dot'=>'#94a3b8'],
    };
@endphp
<div class="rounded-2xl p-5 lg:p-6 mb-6 relative overflow-hidden"
     style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));"
     data-lmt-anim="fade-up">

    <div class="absolute -top-12 -right-12 w-56 h-56 rounded-full bg-white/5 pointer-events-none"></div>
    <div class="absolute -bottom-10 left-1/4 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>

    <div class="relative z-10 grid lg:grid-cols-[1.3fr_1fr] gap-5 items-center">

        {{-- Status + clock --}}
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border bg-white/10 border-white/20 text-white mb-3">
                <span class="w-2 h-2 rounded-full animate-pulse" style="background:{{ $statusMeta['dot'] }};"></span>
                <span x-text="statusLabel || @js($statusMeta['label'])"></span>
            </div>

            <div class="flex items-end gap-2" x-data="liveClock()" x-init="start()">
                <span class="text-white text-4xl lg:text-5xl font-black font-mono tracking-tight" x-text="clock"></span>
                <span class="text-white/70 text-sm font-bold mb-2" x-text="ampm"></span>
            </div>
            <p class="text-white/60 text-xs mt-1">{{ now()->format('l, F j, Y') }} · {{ $tenantTimezone }}</p>

            @if($shift)
                <p class="text-white/80 text-xs font-semibold mt-3">
                    <i data-lucide="moon" class="w-3 h-3 inline -mt-0.5"></i>
                    Shift: {{ $shift->name }} · {{ $shift->label }}
                </p>
            @endif
        </div>

        {{-- Stats + action buttons --}}
        <div class="space-y-3">

            {{-- Mini stats --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white/10 rounded-xl px-3 py-2.5">
                    <p class="text-white/60 text-[10px] font-bold uppercase tracking-wider">Clocked in</p>
                    <p class="text-white font-bold text-sm mt-0.5">
                        {{ $rec?->clock_in_at ? \Carbon\Carbon::parse($rec->clock_in_at)->format('h:i A') : '—' }}
                    </p>
                </div>
                <div class="bg-white/10 rounded-xl px-3 py-2.5">
                    <p class="text-white/60 text-[10px] font-bold uppercase tracking-wider">Worked</p>
                    <p class="text-white font-bold text-sm mt-0.5 font-mono" x-text="formatMins(workedMinutes)"></p>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap gap-2">
                {{-- Clock-in / Clock-out main button --}}
                <template x-if="status === 'not_clocked_in'">
                    <button @click="openClockModal('in')"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm bg-white text-gray-900 hover:bg-white/95 transition-all"
                            style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                        <i data-lucide="fingerprint" class="w-4 h-4"></i> Clock In
                    </button>
                </template>

                <template x-if="status === 'clocked_in'">
                    <button @click="openClockModal('out')"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm bg-white text-gray-900 hover:bg-white/95 transition-all"
                            style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Clock Out
                    </button>
                </template>

                <template x-if="status === 'on_break'">
                    <button @click="endBreak()"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm bg-white text-gray-900 hover:bg-white/95 transition-all"
                            style="box-shadow:0 8px 24px rgba(0,0,0,.18);">
                        <i data-lucide="play" class="w-4 h-4"></i> End Break
                    </button>
                </template>

                <template x-if="status === 'clocked_out'">
                    <div class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm bg-white/20 text-white border border-white/30">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Day Complete
                    </div>
                </template>

                {{-- Start break button (only when clocked in & not on break) --}}
                <template x-if="status === 'clocked_in'">
                    <button @click="openBreakModal()"
                            class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm bg-white/15 text-white border border-white/25 hover:bg-white/25 transition-all">
                        <i data-lucide="coffee" class="w-4 h-4"></i> Break
                    </button>
                </template>

                {{-- Start Overtime — only shown once eligible (admin-approved, shift ended, not already started) --}}
                <template x-if="status === 'clocked_in' && overtimeEligible">
                    <button @click="startOvertime()" :disabled="overtimeSubmitting"
                            class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm bg-white/15 text-white border border-white/25 hover:bg-white/25 transition-all">
                        <i data-lucide="timer" class="w-4 h-4"></i>
                        <span x-text="overtimeSubmitting ? 'Starting…' : 'Start Overtime'"></span>
                    </button>
                </template>
                <template x-if="status === 'clocked_in' && overtimeStartedAt">
                    <span class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm bg-white/10 text-white/80 border border-white/20">
                        <i data-lucide="timer" class="w-4 h-4"></i> Overtime running
                    </span>
                </template>
            </div>
        </div>
    </div>
</div>
