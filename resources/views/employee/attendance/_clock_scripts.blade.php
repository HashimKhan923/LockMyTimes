{{-- Shared Alpine components for the clock-in/out widget — used by both the
     Attendance page (inside its own x-data="attendancePage()" scope) and the
     Dashboard (which wraps the widget in the same scope). Include once per page. --}}
@php
    $attendanceDayBaseUrl = rtrim(route('employee.attendance.day', [$tenantSlug, 'PLACEHOLDER']), '/');
    $clockStatusLabel = [
        'not_clocked_in' => 'Not clocked in',
        'clocked_in'     => 'Clocked in',
        'on_break'       => 'On break',
        'clocked_out'    => 'Day complete',
    ][$clockStatus] ?? 'Not clocked in';
@endphp
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
        statusLabel: @json($clockStatusLabel),
        workedMinutes: 0,
        breakStartedAt: @json($activeBreak?->start_at?->toIso8601String()),
        liveTimer: null,
        pollTimer: null,

        /* overtime */
        overtimeAllowed: @json((bool) ($emp->overtime_allowed ?? false)),
        overtimeStartedAt: null,
        overtimeEligible: false,
        overtimeSubmitting: false,

        /* clock-in/out modal */
        clockOpen: false,
        clockMode: 'in', // 'in' | 'out'
        clockTab: 'web', // 'web' | 'qr'
        clockSubmitting: false,
        clockLocationId: {{ $emp->location_id ?? 'null' }},
        clockNotes: '',
        clockQrToken: '',
        clockGeo: { lat: null, lng: null, accuracy: null, err: null },

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
                this.overtimeAllowed = !!d.overtime_allowed;
                this.overtimeStartedAt = d.overtime_started_at || null;
                this.overtimeEligible = !!d.overtime_eligible;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            } catch (e) {}
        },

        /* ─── Overtime ─── */
        async startOvertime() {
            if (this.overtimeSubmitting) return;
            this.overtimeSubmitting = true;
            try {
                const r = await fetch(@json(route('employee.attendance.overtime.start', $tenantSlug)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const d = await r.json().catch(() => ({}));
                if (r.ok && d.success) {
                    window.lmtToast && lmtToast(d.message || 'Overtime started.', 'success');
                    await this.refreshStatus();
                } else {
                    window.lmtToast && lmtToast(d.message || 'Could not start overtime.', 'error');
                }
            } catch (e) {
                window.lmtToast && lmtToast('Network error. Please retry.', 'error');
            } finally {
                this.overtimeSubmitting = false;
            }
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
            this.clockOpen = true;
            this.captureGeo();
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        closeClockModal() {
            this.clockOpen = false;
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

        /* ─── Day drawer (Attendance page's calendar/list only — harmless no-op elsewhere) ─── */
        async openDay(date) {
            this.drawerOpen = true;
            this.drawerLoading = true;
            this.drawerHtml = '';
            try {
                const url = @json($attendanceDayBaseUrl).replace('PLACEHOLDER', date);
                const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const d = await r.json();
                this.drawerHtml = d.html || '<p class="p-6 text-sm text-gray-800">Could not load.</p>';
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
