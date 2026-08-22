<div x-show="clockOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="closeClockModal()">
    <div class="lmt-modal" style="max-width:560px;" @click.outside="closeClockModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
                <span x-text="clockMode === 'in' ? 'Clock In' : 'Clock Out'"></span>
            </h3>
            <button @click="closeClockModal()" class="text-gray-800 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Method tabs --}}
        <div class="inline-flex p-1 bg-gray-100 dark:bg-slate-800 rounded-xl text-xs font-bold mb-5">
            <button @click="clockTab='web'"
                    :class="clockTab==='web' ? 'bg-white dark:bg-slate-900 shadow text-gray-900 dark:text-white' : 'text-gray-800'"
                    class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition">
                <i data-lucide="laptop" class="w-3.5 h-3.5"></i> Web
            </button>
            <button @click="clockTab='qr'"
                    :class="clockTab==='qr' ? 'bg-white dark:bg-slate-900 shadow text-gray-900 dark:text-white' : 'text-gray-800'"
                    class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition">
                <i data-lucide="qr-code" class="w-3.5 h-3.5"></i> QR Code
            </button>
        </div>

        {{-- Geo banner --}}
        <div class="mb-4 p-3 rounded-xl flex items-start gap-3 text-xs"
             :class="clockGeo.err ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-300'">
            <i :data-lucide="clockGeo.err ? 'map-pin-off' : (clockGeo.lat ? 'map-pin' : 'loader')"
               class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <p class="font-bold" x-show="!clockGeo.lat && !clockGeo.err">Getting your location…</p>
                <p class="font-bold" x-show="clockGeo.lat && !clockGeo.err">
                    Location captured
                    <span class="font-mono text-[10px] ml-1 text-gray-800" x-text="`(±${clockGeo.accuracy}m)`"></span>
                </p>
                <p class="font-bold" x-show="clockGeo.err" x-text="clockGeo.err"></p>
            </div>
            <button @click="captureGeo()" type="button" class="text-xs font-bold hover:underline" style="color:var(--brand-500);">
                Retry
            </button>
        </div>

        {{-- Web tab --}}
        <div x-show="clockTab === 'web'">
            @if(($emp->employment_mode ?? 'onsite') === 'remote')
            <div class="mb-4 p-3 rounded-xl flex items-start gap-3 text-xs bg-brand-50 text-brand-700 border border-brand-100">
                <i data-lucide="globe" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <p class="font-bold">You're set to Remote — clock in from anywhere, no geofence check applies.</p>
            </div>
            @endif
            <label class="lmt-label">Location</label>
            <select class="lmt-input" x-model="clockLocationId">
                <option value="">Pick a location…</option>
                @foreach($assignedLocs as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
            <p class="lmt-help">Your assigned location is selected by default.</p>
        </div>

        {{-- QR tab --}}
        <div x-show="clockTab === 'qr'">
            <label class="lmt-label">Scan or paste QR code</label>
            <input type="text" class="lmt-input font-mono" x-model="clockQrToken"
                   placeholder="Paste QR token here or scan with your camera"/>
            <p class="lmt-help">Ask your admin for a printed QR code. Scanner support via camera is coming soon.</p>
        </div>

        {{-- Notes --}}
        <div class="mt-4">
            <label class="lmt-label">Notes <span class="text-gray-800 font-normal">(optional)</span></label>
            <input type="text" class="lmt-input" x-model="clockNotes" maxlength="255"
                   placeholder="e.g. Working from client site today"/>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-2 mt-6">
            <button @click="closeClockModal()" class="lmt-btn-secondary">Cancel</button>
            <button @click="submitClock()" class="lmt-btn-primary" :disabled="clockSubmitting">
                <template x-if="!clockSubmitting">
                    <span class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span x-text="clockMode === 'in' ? 'Clock In Now' : 'Clock Out Now'"></span>
                    </span>
                </template>
                <span x-show="clockSubmitting" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Submitting…
                </span>
            </button>
        </div>
    </div>
</div>