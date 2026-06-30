@extends('layouts.employee')

@section('title', 'My Attendance')
@section('page-title', 'My Attendance')

@push('head')
<style>
    /* Day cell sizing tweaks */
    .cal-cell { aspect-ratio: 1 / 1; min-height: 64px; }
    @media (min-width: 1024px) { .cal-cell { min-height: 88px; } }

    /* Status colors used in calendar + list */
    .st-present { background:#10b981; }
    .st-late    { background:#f59e0b; }
    .st-absent  { background:#ef4444; }
    .st-leave   { background:#8b5cf6; }
    .st-half    { background:#3b82f6; }
    .st-holiday { background:#06b6d4; }
    .st-weekend { background:#cbd5e1; }
    .st-future  { background:transparent; border:1px dashed #e5e7eb; }
    .st-none    { background:#e5e7eb; }

    /* Drawer */
    .drawer-backdrop { background:rgba(15,23,42,.45); backdrop-filter:blur(4px); }
    .drawer-panel   { max-width:480px; }
</style>
@endpush

@section('content')

@php
    $statusMeta = match($clockStatus) {
        'not_clocked_in' => ['label'=>'Not clocked in', 'dot'=>'#94a3b8'],
        'clocked_in'     => ['label'=>'Clocked in',     'dot'=>'#10b981'],
        'on_break'       => ['label'=>'On break',       'dot'=>'#f59e0b'],
        'clocked_out'    => ['label'=>'Day complete',   'dot'=>'#6366f1'],
    };

    $prevMonth = $month->copy()->subMonth()->format('Y-m');
    $nextMonth = $month->copy()->addMonth()->format('Y-m');
    $thisMonth = \Carbon\Carbon::today()->startOfMonth()->format('Y-m');
    $isCurrent = $month->format('Y-m') === $thisMonth;
@endphp

<div x-data="attendancePage()" x-init="init()">

    {{-- ═══════════════════════════════════════════════════════════════
         TODAY'S CLOCK CARD
    ═══════════════════════════════════════════════════════════════ --}}
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

                @if($todayShift)
                    <p class="text-white/80 text-xs font-semibold mt-3">
                        <i data-lucide="moon" class="w-3 h-3 inline -mt-0.5"></i>
                        Shift: {{ $todayShift->name }} · {{ $todayShift->label }}
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
                            {{ $todayRec?->clock_in_at ? \Carbon\Carbon::parse($todayRec->clock_in_at)->format('h:i A') : '—' }}
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
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MONTHLY SUMMARY
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 lg:gap-4 mb-6">
        @php
            $cards = [
                ['lbl'=>'Present',  'val'=>$summary->present_days,  'icon'=>'check-circle','color'=>'bg-emerald-50 text-emerald-600'],
                ['lbl'=>'Late',     'val'=>$summary->late_count,    'icon'=>'alert-circle','color'=>'bg-amber-50 text-amber-600'],
                ['lbl'=>'Absent',   'val'=>$summary->absent_days,   'icon'=>'x-circle',    'color'=>'bg-red-50 text-red-600'],
                ['lbl'=>'On Leave', 'val'=>$summary->leave_days,    'icon'=>'palmtree',    'color'=>'bg-violet-50 text-violet-600'],
                ['lbl'=>'Hours',    'val'=>number_format($summary->total_hours, 1).'h', 'icon'=>'clock','color'=>'bg-brand-50 text-brand-600'],
            ];
        @endphp
        @foreach($cards as $i => $c)
            <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.04 }}">
                <div class="flex-1">
                    <p class="lmt-stat-label">{{ $c['lbl'] }}</p>
                    <p class="lmt-stat-value">{{ $c['val'] }}</p>
                </div>
                <div class="lmt-stat-icon {{ $c['color'] }}">
                    <i data-lucide="{{ $c['icon'] }}" class="w-5 h-5"></i>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TOOLBAR — month picker + view switcher + export
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card mb-6" data-lmt-anim="fade-up">
        <div class="flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">

            {{-- Month nav --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('employee.attendance.index', [$tenantSlug, 'month' => $prevMonth, 'view' => $view]) }}"
                   class="w-9 h-9 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center transition-colors">
                    <i data-lucide="chevron-left" class="w-4 h-4 text-gray-500"></i>
                </a>
                <div class="text-base lg:text-lg font-black text-gray-900 dark:text-slate-100 min-w-[170px] text-center">
                    {{ $month->format('F Y') }}
                </div>
                <a href="{{ route('employee.attendance.index', [$tenantSlug, 'month' => $nextMonth, 'view' => $view]) }}"
                   class="w-9 h-9 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center transition-colors">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-500"></i>
                </a>
                @if(! $isCurrent)
                    <a href="{{ route('employee.attendance.index', [$tenantSlug, 'view' => $view]) }}"
                       class="hidden lg:inline-flex text-xs font-bold ml-2 px-2.5 py-1 rounded-full"
                       style="background:var(--brand-50); color:var(--brand-700);">
                        Today
                    </a>
                @endif
            </div>

            {{-- View switcher + export --}}
            <div class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex p-1 bg-gray-100 dark:bg-slate-800 rounded-xl text-xs font-bold">
                    <a href="{{ route('employee.attendance.index', [$tenantSlug, 'view' => 'calendar', 'month' => $month->format('Y-m')]) }}"
                       class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition
                              {{ $view === 'calendar' ? 'bg-white dark:bg-slate-900 shadow text-gray-900 dark:text-white' : 'text-gray-500' }}">
                        <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i> Calendar
                    </a>
                    <a href="{{ route('employee.attendance.index', [$tenantSlug, 'view' => 'list', 'month' => $month->format('Y-m')]) }}"
                       class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition
                              {{ $view === 'list' ? 'bg-white dark:bg-slate-900 shadow text-gray-900 dark:text-white' : 'text-gray-500' }}">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i> List
                    </a>
                </div>

                <a href="{{ route('employee.attendance.export', [$tenantSlug, 'month' => $month->format('Y-m')]) }}"
                   class="lmt-btn-secondary lmt-btn-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Export CSV
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         VIEW BODY
    ═══════════════════════════════════════════════════════════════ --}}
    @if($view === 'calendar')
        @include('employee.attendance._calendar')
    @else
        @include('employee.attendance._list')
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         DAY DETAIL DRAWER (rendered on demand via AJAX)
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="drawerOpen" x-cloak
         class="fixed inset-0 z-50 flex justify-end"
         x-transition.opacity>
        <div class="absolute inset-0 drawer-backdrop" @click="closeDrawer()"></div>
        <div class="relative drawer-panel w-full bg-white dark:bg-slate-900 shadow-2xl h-full overflow-y-auto"
             x-show="drawerOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">

            <button @click="closeDrawer()"
                    class="absolute top-3 right-3 w-9 h-9 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center justify-center transition-colors z-10">
                <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
            </button>

            <div x-show="drawerLoading" class="flex items-center justify-center py-20 text-sm text-gray-400 gap-2">
                <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Loading day details…
            </div>

            <div x-show="!drawerLoading" x-html="drawerHtml"></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         CLOCK-IN / CLOCK-OUT MODAL
    ═══════════════════════════════════════════════════════════════ --}}
    @include('employee.attendance._clockin_modal')

    {{-- ═══════════════════════════════════════════════════════════════
         BREAK MODAL
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="breakOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="breakOpen=false">
        <div class="lmt-modal" @click.outside="breakOpen=false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-black text-gray-900 dark:text-slate-100">Start a Break</h3>
                <button @click="breakOpen=false" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <p class="text-sm text-gray-500 mb-4">Pick a break type. We'll start the timer right away.</p>

            <div class="space-y-2 mb-5">
                @php
                    $bt = [
                        ['key'=>'lunch',    'icon'=>'utensils', 'label'=>'Lunch'],
                        ['key'=>'tea',      'icon'=>'coffee',   'label'=>'Tea / Coffee'],
                        ['key'=>'personal', 'icon'=>'user',     'label'=>'Personal'],
                        ['key'=>'other',    'icon'=>'more-horizontal','label'=>'Other'],
                    ];
                @endphp
                @foreach($bt as $b)
                    <button @click="breakType='{{ $b['key'] }}'"
                            :class="breakType==='{{ $b['key'] }}' ? 'border-brand-500 bg-brand-50' : 'border-gray-200 hover:border-gray-300'"
                            class="w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                             :class="breakType==='{{ $b['key'] }}' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-500'">
                            <i data-lucide="{{ $b['icon'] }}" class="w-4 h-4"></i>
                        </div>
                        <span class="font-bold text-sm text-gray-900 dark:text-slate-100">{{ $b['label'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <button @click="breakOpen=false" class="lmt-btn-secondary">Cancel</button>
                <button @click="startBreak()" class="lmt-btn-primary" :disabled="breakSubmitting">
                    <span x-show="!breakSubmitting"><i data-lucide="play" class="w-4 h-4"></i> Start Break</span>
                    <span x-show="breakSubmitting">Starting…</span>
                </button>
            </div>
        </div>
    </div>

</div>{{-- /attendancePage --}}

@php $attendanceDayBaseUrl = rtrim(route('employee.attendance.day', [$tenantSlug, 'PLACEHOLDER']), '/'); @endphp
@push('scripts')
<script>
function liveClock() {
    return {
        clock: '', ampm: '', tz: @json($tenantTimezone ?? config('app.timezone')),
        start() { this.update(); setInterval(()=>this.update(), 1000); },
        update() {
            const parts = new Intl.DateTimeFormat('en-US', {
                timeZone: this.tz, hour: '2-digit', minute: '2-digit', second:'2-digit', hour12: true
            }).formatToParts(new Date());
            const get = (t) => parts.find(p => p.type === t)?.value ?? '';
            this.clock = `${get('hour')}:${get('minute')}:${get('second')}`;
            this.ampm  = get('dayPeriod');
        },
    };
}

function attendancePage() {
    return {
        /* state */
        status: @json($clockStatus),
        statusLabel: @json($statusMeta['label']),
        workedMinutes: 0,
        breakStartedAt: @json($activeBreak?->start_at?->toIso8601String()),
        liveTimer: null,
        pollTimer: null,

        /* clock-in/out modal */
        clockOpen: false,
        clockMode: 'in', // 'in' | 'out'
        clockTab: 'web', // 'web' | 'qr' | 'selfie'
        clockSubmitting: false,
        clockLocationId: {{ $emp->location_id ?? 'null' }},
        clockNotes: '',
        clockQrToken: '',
        clockSelfie: null,    // dataURL
        clockGeo: { lat: null, lng: null, accuracy: null, err: null },
        videoStream: null,

        /* drawer */
        drawerOpen: false,
        drawerLoading: false,
        drawerHtml: '',

        /* break modal */
        breakOpen: false,
        breakType: 'tea',
        breakSubmitting: false,

        async init() {
            await this.refreshStatus();
            this.startLiveTimer();
            // Poll every 30s so multi-device clock-ins stay in sync
            this.pollTimer = setInterval(() => this.refreshStatus(), 30000);
        },

        startLiveTimer() {
            if (this.liveTimer) clearInterval(this.liveTimer);
            this.liveTimer = setInterval(() => {
                if (this.status === 'clocked_in' || this.status === 'on_break') {
                    // Worked minutes only increases while clocked_in (not on break)
                    if (this.status === 'clocked_in') this.workedMinutes++;
                }
            }, 60000); // tick once per minute
        },

        async refreshStatus() {
            try {
                const r = await fetch(@json(route('employee.attendance.status', $tenantSlug)), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!r.ok) return;
                const d = await r.json();
                this.status = d.status;
                this.workedMinutes = Math.round(d.worked_minutes || 0);
                this.statusLabel = ({
                    'not_clocked_in': 'Not clocked in',
                    'clocked_in':     'Clocked in',
                    'on_break':       'On break',
                    'clocked_out':    'Day complete',
                })[d.status] || 'Unknown';
                this.$nextTick(() => window.lucide && lucide.createIcons());
            } catch (e) {}
        },

        formatMins(m) {
            const h = Math.floor(m / 60);
            const mm = m % 60;
            return `${h}h ${String(mm).padStart(2,'0')}m`;
        },

        /* ─── Clock-in modal ─── */
        openClockModal(mode) {
            this.clockMode = mode;
            this.clockTab = 'web';
            this.clockNotes = '';
            this.clockQrToken = '';
            this.clockSelfie = null;
            this.clockOpen = true;
            this.captureGeo();
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        closeClockModal() {
            this.clockOpen = false;
            this.stopCamera();
        },
        captureGeo() {
            this.clockGeo = { lat: null, lng: null, accuracy: null, err: null };
            if (!navigator.geolocation) {
                this.clockGeo.err = 'Geolocation not supported by your browser.';
                return;
            }
            navigator.geolocation.getCurrentPosition(
                p => this.clockGeo = { lat: p.coords.latitude, lng: p.coords.longitude, accuracy: Math.round(p.coords.accuracy), err: null },
                e => this.clockGeo.err = e.message || 'Location permission denied.',
                { enableHighAccuracy: true, timeout: 8000 }
            );
        },

        /* Camera (selfie) */
        async startCamera() {
            try {
                this.videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: 480, height: 480 }, audio: false
                });
                const v = this.$refs.video;
                if (v) { v.srcObject = this.videoStream; v.play(); }
            } catch (e) {
                window.lmtToast && lmtToast('Camera permission denied or unavailable.', 'error');
            }
        },
        stopCamera() {
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(t => t.stop());
                this.videoStream = null;
            }
        },
        snapSelfie() {
            const v = this.$refs.video;
            if (!v || !v.videoWidth) return;
            const c = document.createElement('canvas');
            c.width = v.videoWidth; c.height = v.videoHeight;
            const ctx = c.getContext('2d');
            ctx.translate(c.width, 0); ctx.scale(-1, 1); // mirror
            ctx.drawImage(v, 0, 0);
            this.clockSelfie = c.toDataURL('image/jpeg', 0.85);
            this.stopCamera();
        },
        retakeSelfie() { this.clockSelfie = null; this.startCamera(); },

        async submitClock() {
            if (this.clockSubmitting) return;
            this.clockSubmitting = true;
            const url = this.clockMode === 'in'
                ? @json(route('employee.attendance.clock-in',  $tenantSlug))
                : @json(route('employee.attendance.clock-out', $tenantSlug));
            const body = {
                source: this.clockTab === 'qr' ? 'qr' : 'web',
                lat: this.clockGeo.lat,
                lng: this.clockGeo.lng,
                notes: this.clockNotes || null,
            };
            if (this.clockTab === 'qr')    body.qr_token = this.clockQrToken;
            if (this.clockTab !== 'qr')    body.location_id = this.clockLocationId;
            if (this.clockSelfie)          body.selfie = this.clockSelfie;

            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(body),
                });
                const data = await r.json().catch(() => ({}));
                if (r.ok && data.success) {
                    window.lmtToast && lmtToast(data.message || 'Done!', 'success');
                    this.closeClockModal();
                    await this.refreshStatus();
                    setTimeout(() => location.reload(), 800);
                } else {
                    window.lmtToast && lmtToast(data.message || 'Could not complete the action.', 'error');
                }
            } catch (e) {
                window.lmtToast && lmtToast('Network error. Please retry.', 'error');
            } finally {
                this.clockSubmitting = false;
            }
        },

        /* ─── Breaks ─── */
        openBreakModal() { this.breakOpen = true; this.breakType = 'tea'; this.$nextTick(() => window.lucide && lucide.createIcons()); },
        async startBreak() {
            if (this.breakSubmitting) return;
            this.breakSubmitting = true;
            try {
                const r = await fetch(@json(route('employee.attendance.break.start', $tenantSlug)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ break_type: this.breakType }),
                });
                const d = await r.json().catch(()=>({}));
                if (r.ok && d.success) {
                    window.lmtToast && lmtToast(d.message, 'success');
                    this.breakOpen = false;
                    await this.refreshStatus();
                } else {
                    window.lmtToast && lmtToast(d.message || 'Could not start break.', 'error');
                }
            } finally { this.breakSubmitting = false; }
        },
        async endBreak() {
            try {
                const r = await fetch(@json(route('employee.attendance.break.end', $tenantSlug)), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const d = await r.json().catch(()=>({}));
                if (r.ok && d.success) {
                    window.lmtToast && lmtToast(d.message, 'success');
                    await this.refreshStatus();
                } else {
                    window.lmtToast && lmtToast(d.message || 'Could not end break.', 'error');
                }
            } catch(e) {}
        },

        /* ─── Day drawer ─── */
        async openDay(date) {
            this.drawerOpen = true;
            this.drawerLoading = true;
            this.drawerHtml = '';
            try {
                const url = @json($attendanceDayBaseUrl).replace('PLACEHOLDER', date);
                const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const d = await r.json();
                this.drawerHtml = d.html || '<p class="p-6 text-sm text-gray-500">Could not load.</p>';
            } catch(e) {
                this.drawerHtml = '<p class="p-6 text-sm text-red-500">Failed to load day details.</p>';
            } finally {
                this.drawerLoading = false;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            }
        },
        closeDrawer() { this.drawerOpen = false; this.drawerHtml = ''; },
    };
}
</script>
@endpush

@endsection