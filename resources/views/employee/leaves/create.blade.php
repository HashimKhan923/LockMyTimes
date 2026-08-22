@extends('layouts.employee')

@section('title', 'Apply for Leave')
@section('page-title', 'Apply for Leave')

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Back link --}}
    <a href="{{ route('employee.leaves.index', $tenantSlug) }}"
       class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-800 hover:text-gray-700 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to leaves
    </a>

    <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
            Apply for Leave
        </h1>
        <p class="text-sm text-gray-800 mt-1">Submit a new leave request. Your manager will be notified for approval if required.</p>
    </div>

    @if($errors->any())
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm space-y-0.5">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('employee.leaves.store', $tenantSlug) }}" method="POST" enctype="multipart/form-data"
          class="lmt-card space-y-6"
          x-data="leaveApply()" x-init="init()">
        @csrf

        {{-- 1. Leave type --}}
        <div>
            <label class="lmt-label">Leave type <span class="text-red-500">*</span></label>
            <div class="grid sm:grid-cols-2 gap-2.5">
                @foreach($types as $t)
                    <label class="block cursor-pointer">
                        <input type="radio" name="leave_type_id" value="{{ $t->id }}"
                               class="sr-only" x-model.number="form.leave_type_id"
                               @change="onTypeChange({{ $t->id }})"
                               {{ (int) old('leave_type_id') === (int) $t->id ? 'checked' : '' }}
                               required/>
                        <div class="p-3 rounded-2xl border-2 transition-all"
                             :class="form.leave_type_id === {{ $t->id }} ? 'border-brand-500 bg-brand-50/40' : 'border-gray-200 hover:border-gray-300'">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full" style="background: {{ $t->color ?? '#6C7DF7' }};"></span>
                                <span class="font-bold text-sm text-gray-900 dark:text-slate-100 flex-1">{{ $t->name }}</span>
                                @if(! $t->is_paid)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-800 uppercase">Unpaid</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <span class="text-gray-800">Available</span>
                                <span class="font-mono font-bold text-gray-900 dark:text-slate-100">
                                    {{ $t->_available }} / {{ $t->_total }} days
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                @if($t->requires_documentation)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">Docs required</span>
                                @endif
                                @if($t->notice_days_required > 0)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700">{{ $t->notice_days_required }}d notice</span>
                                @endif
                                @if($t->max_consecutive_days > 0)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-violet-50 text-violet-700">Max {{ $t->max_consecutive_days }}d</span>
                                @endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('leave_type_id') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        {{-- 2. Dates --}}
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="lmt-label">From <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" required
                       min="{{ $minDate }}"
                       class="lmt-input"
                       value="{{ old('start_date') }}"
                       x-model="form.start_date" @change="recalc()"/>
                @error('start_date') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="lmt-label">To <span class="text-red-500">*</span></label>
                <input type="date" name="end_date" required
                       :min="form.start_date || '{{ $minDate }}'"
                       class="lmt-input"
                       value="{{ old('end_date') }}"
                       x-model="form.end_date" @change="recalc()"/>
                @error('end_date') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Day part toggle (only when single-day) --}}
        <div x-show="isSingleDay" x-cloak>
            <label class="lmt-label">Day portion</label>
            <div class="inline-flex p-1 bg-gray-100 dark:bg-slate-800 rounded-xl text-xs font-bold">
                @foreach(['full' => 'Full day', 'first_half' => 'First half', 'second_half' => 'Second half'] as $key => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="day_part" value="{{ $key }}" class="sr-only"
                               x-model="form.day_part" @change="recalc()"
                               {{ old('day_part', 'full') === $key ? 'checked' : '' }}/>
                        <span class="px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition"
                              :class="form.day_part === '{{ $key }}' ? 'bg-white dark:bg-slate-900 shadow text-gray-900 dark:text-white' : 'text-gray-800'">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- 3. Live calculation panel --}}
        <div class="rounded-2xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50 dark:bg-slate-800 flex items-center gap-2 border-b border-gray-100 dark:border-slate-700">
                <i data-lucide="calculator" class="w-4 h-4" style="color:var(--brand-500);"></i>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-800">Calculation</span>
                <span x-show="loading" class="ml-auto text-[10px] text-gray-800">computing…</span>
            </div>
            <div class="p-4">
                <template x-if="!calc">
                    <p class="text-sm text-gray-800">Pick dates to see the day count and remaining balance.</p>
                </template>

                <template x-if="calc">
                    <div>
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div class="p-3 rounded-xl bg-brand-50" style="background:var(--brand-50);">
                                <p class="text-[10px] font-bold uppercase tracking-wider" style="color:var(--brand-700);">Days requested</p>
                                <p class="text-xl font-black font-mono mt-1" style="color:var(--brand-700);" x-text="calc.total"></p>
                            </div>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-800">Working days</p>
                                <p class="text-xl font-black font-mono mt-1 text-gray-900 dark:text-slate-100" x-text="calc.workdays"></p>
                            </div>
                            <div class="p-3 rounded-xl"
                                 :class="(calc.remaining ?? 0) < 0 ? 'bg-red-50' : 'bg-emerald-50'">
                                <p class="text-[10px] font-bold uppercase tracking-wider"
                                   :class="(calc.remaining ?? 0) < 0 ? 'text-red-700' : 'text-emerald-700'">
                                    Balance after
                                </p>
                                <p class="text-xl font-black font-mono mt-1"
                                   :class="(calc.remaining ?? 0) < 0 ? 'text-red-700' : 'text-emerald-700'"
                                   x-text="calc.remaining !== null ? calc.remaining : '—'"></p>
                            </div>
                        </div>

                        <template x-if="calc.holidays.length">
                            <div class="text-xs text-gray-800 mb-2">
                                <span class="font-bold">Holidays in range:</span>
                                <template x-for="(h, i) in calc.holidays" :key="i">
                                    <span class="inline-block ml-1 px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 font-bold text-[10px]"
                                          x-text="h.date + ' · ' + h.name"></span>
                                </template>
                            </div>
                        </template>

                        <template x-if="calc.warnings.length">
                            <div class="space-y-1 mt-2">
                                <template x-for="(w, i) in calc.warnings" :key="i">
                                    <p class="text-xs font-semibold text-amber-700 flex items-center gap-1.5">
                                        <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                        <span x-text="w"></span>
                                    </p>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- 4. Reason --}}
        <div>
            <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
            <textarea name="reason" required minlength="5" maxlength="1000"
                      class="lmt-textarea" rows="3"
                      placeholder="Explain briefly why you need this leave…">{{ old('reason') }}</textarea>
            <p class="lmt-help">5–1000 characters.</p>
            @error('reason') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        {{-- 5. Contact during leave --}}
        <div>
            <label class="lmt-label">Contact during leave <span class="text-gray-800 font-normal">(optional)</span></label>
            <input type="text" name="contact_during_leave" maxlength="255"
                   class="lmt-input"
                   placeholder="e.g. +92 300 1234567 or alternate-email@example.com"
                   value="{{ old('contact_during_leave') }}"/>
        </div>

        {{-- 6. Attachment --}}
        <div>
            <label class="lmt-label">
                Attachment
                <span class="text-gray-800 font-normal" x-show="!selectedType?.requires_documentation">(optional)</span>
                <span class="text-red-500" x-show="selectedType?.requires_documentation">*</span>
            </label>
            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-bold file:text-sm file:cursor-pointer
                          file:bg-gray-100 hover:file:bg-gray-200 dark:file:bg-slate-700 dark:hover:file:bg-slate-600 dark:file:text-slate-200"/>
            <p class="lmt-help">PDF, JPG, PNG, DOC, DOCX. Max 5 MB.</p>
            @error('attachment') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        {{-- Footer actions --}}
        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2 border-t border-gray-100 dark:border-slate-700">
            <a href="{{ route('employee.leaves.index', $tenantSlug) }}" class="lmt-btn-secondary">Cancel</a>
            <button type="submit" class="lmt-btn-primary">
                <i data-lucide="send" class="w-4 h-4"></i>
                Submit Request
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function leaveApply() {
    return {
        form: {
            leave_type_id: {{ old('leave_type_id', 'null') ?: 'null' }},
            start_date: @json(old('start_date', '')),
            end_date: @json(old('end_date', '')),
            day_part: @json(old('day_part', 'full')),
        },
        types: {{ Js::from($types->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'requires_documentation' => $t->requires_documentation])->values()) }},
        get selectedType() {
            return this.types.find(t => t.id === this.form.leave_type_id);
        },
        get isSingleDay() {
            return this.form.start_date && this.form.end_date && this.form.start_date === this.form.end_date;
        },
        calc: null,
        loading: false,
        _timer: null,

        init() {
            this.$watch('form.start_date', () => this.recalc());
            this.$watch('form.end_date',   () => this.recalc());
            if (this.form.start_date && this.form.end_date) this.recalc();
        },

        onTypeChange(id) {
            this.form.leave_type_id = id;
            this.recalc();
        },

        recalc() {
            if (!this.form.start_date || !this.form.end_date) return;
            if (this.form.end_date < this.form.start_date) return;

            clearTimeout(this._timer);
            this._timer = setTimeout(async () => {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        start_date: this.form.start_date,
                        end_date:   this.form.end_date,
                        day_part:   this.isSingleDay ? this.form.day_part : 'full',
                    });
                    if (this.form.leave_type_id) params.set('leave_type_id', this.form.leave_type_id);

                    const r = await fetch(@json(route('employee.leaves.calculate', $tenantSlug)) + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (r.ok) this.calc = await r.json();
                } catch (e) {} finally {
                    this.loading = false;
                    this.$nextTick(() => window.lucide && lucide.createIcons());
                }
            }, 250);
        },
    };
}
</script>
@endpush

@endsection