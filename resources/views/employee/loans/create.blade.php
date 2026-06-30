@extends('layouts.employee')

@section('title', 'Apply for Loan')
@section('page-title', 'Apply for Loan')

@section('content')
<div class="max-w-4xl mx-auto">

    @php
        $cur = $tenantCurrency ?? 'USD';
        $sym = match($cur) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY', 'CNY' => '¥',
            'INR' => '₹',
            'PKR' => 'Rs',
            'AED' => 'د.إ',
            'SAR' => '﷼',
            default => $cur.' ',
        };
    @endphp

    {{-- Back link --}}
    <a href="{{ route('employee.loans.index', $tenantSlug) }}"
       class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to loans
    </a>

    <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
            Apply for a Loan
        </h1>
        <p class="text-sm text-gray-500 mt-1">Submit a loan application for approval by your HR / finance team.</p>
    </div>

    {{-- Profile snapshot --}}
    <div class="lmt-card p-4 mb-5 flex flex-wrap items-center gap-4 text-xs" data-lmt-anim="fade-up">
        <div class="flex items-center gap-2">
            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
            <span class="text-gray-500">Service:</span>
            <span class="font-bold text-gray-900 dark:text-slate-100">{{ $serviceMonths }} months</span>
        </div>
        @if($baseSalary > 0)
            <div class="flex items-center gap-2">
                <i data-lucide="dollar-sign" class="w-4 h-4 text-gray-400"></i>
                <span class="text-gray-500">Base salary:</span>
                <span class="font-bold text-gray-900 dark:text-slate-100">{{ $sym }}{{ number_format($baseSalary, 2) }}</span>
            </div>
        @endif
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

    @if(session('error'))
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    @if($types->isEmpty())
        <div class="lmt-alert lmt-alert-warning">
            <i data-lucide="info" class="w-5 h-5 shrink-0"></i>
            <p>No loan types are currently available. Please contact your administrator.</p>
        </div>
    @else
        <form action="{{ route('employee.loans.store', $tenantSlug) }}" method="POST"
              class="lmt-card space-y-6"
              x-data="loanApply()" x-init="init()">
            @csrf

            {{-- 1. Loan type --}}
            <div>
                <label class="lmt-label">Loan type <span class="text-red-500">*</span></label>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                    @foreach($types as $t)
                        <label class="block {{ $t->_eligible ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                            <input type="radio" name="loan_type_id" value="{{ $t->id }}"
                                   class="sr-only"
                                   x-model.number="form.loan_type_id"
                                   @change="onTypeChange({{ $t->id }})"
                                   {{ (int) old('loan_type_id') === (int) $t->id ? 'checked' : '' }}
                                   @if(! $t->_eligible) disabled @endif
                                   required/>
                            <div class="p-3 rounded-2xl border-2 transition-all h-full"
                                 :class="form.loan_type_id === {{ $t->id }} ? 'border-brand-500 bg-brand-50/40' : 'border-gray-200 hover:border-gray-300 dark:border-slate-700 dark:hover:border-slate-600'">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background: {{ $t->color ?? '#6C7DF7' }};"></span>
                                    <span class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $t->name }}</span>
                                </div>
                                @if($t->description)
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mb-2 line-clamp-2">{{ $t->description }}</p>
                                @endif
                                <div class="space-y-1 text-[10px]">
                                    @if($t->default_interest_rate > 0)
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Interest</span>
                                            <span class="font-mono font-bold text-gray-700 dark:text-slate-200">{{ number_format($t->default_interest_rate, 2) }}% p.a.</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Tenure</span>
                                        <span class="font-mono font-bold text-gray-700 dark:text-slate-200">{{ $t->min_tenure_months }}–{{ $t->max_tenure_months }} months</span>
                                    </div>
                                    @if($t->_effective_max)
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Max</span>
                                            <span class="font-mono font-bold text-gray-700 dark:text-slate-200">{{ $sym }}{{ number_format($t->_effective_max, 0) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @if($t->requires_guarantor)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 uppercase">Guarantor</span>
                                    @endif
                                    @if($t->requires_collateral)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300 uppercase">Collateral</span>
                                    @endif
                                    @if($t->auto_deduct_from_payroll)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 uppercase">Payroll EMI</span>
                                    @endif
                                </div>
                                @if(! $t->_eligible)
                                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700">
                                        @foreach($t->_block_reasons as $reason)
                                            <p class="text-[10px] text-red-500 font-bold">⚠ {{ $reason }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('loan_type_id') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>

            {{-- 2. Amount + Tenure --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Loan amount <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400 pointer-events-none">{{ $sym }}</span>
                        <input type="number" name="principal_amount" required min="1" step="0.01"
                               class="lmt-input pl-8 font-mono"
                               x-model.number="form.principal_amount"
                               placeholder="0.00"
                               value="{{ old('principal_amount') }}"/>
                    </div>
                    <p x-show="selectedType?._effective_max" x-cloak class="lmt-help">
                        Maximum available: <span x-text="formatMoney(selectedType?._effective_max)" class="font-bold"></span>
                    </p>
                    @error('principal_amount') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="lmt-label">Tenure (months) <span class="text-red-500">*</span></label>
                    <input type="number" name="tenure_months" required min="1" max="120" step="1"
                           class="lmt-input font-mono"
                           x-model.number="form.tenure_months"
                           placeholder="12"
                           value="{{ old('tenure_months') }}"/>
                    <p x-show="selectedType" x-cloak class="lmt-help">
                        Allowed range: <span x-text="selectedType?.min_tenure_months + '–' + selectedType?.max_tenure_months" class="font-bold"></span> months
                    </p>
                    @error('tenure_months') <p class="lmt-err">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- 3. Live EMI preview --}}
            <div x-show="hasEmiData" x-cloak
                 class="rounded-2xl p-5 grid grid-cols-3 gap-4"
                 style="background:linear-gradient(135deg,var(--brand-50),#ede9fe);">
                <div>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Monthly EMI</p>
                    <p class="text-xl font-black font-mono mt-1" style="color:var(--brand-600);">
                        {{ $sym }}<span x-text="formatNumber(emi.emi)"></span>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total interest</p>
                    <p class="text-xl font-black font-mono mt-1 text-gray-700 dark:text-slate-200">
                        {{ $sym }}<span x-text="formatNumber(emi.total_interest)"></span>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Total payable</p>
                    <p class="text-xl font-black font-mono mt-1 text-gray-900 dark:text-slate-100">
                        {{ $sym }}<span x-text="formatNumber(emi.total_amount)"></span>
                    </p>
                </div>
            </div>

            {{-- 4. First EMI date --}}
            <div>
                <label class="lmt-label">First EMI date <span class="text-red-500">*</span></label>
                <input type="date" name="first_emi_date" required
                       min="{{ now()->addDay()->toDateString() }}"
                       class="lmt-input"
                       value="{{ old('first_emi_date', now()->addMonth()->startOfMonth()->toDateString()) }}"/>
                <p class="lmt-help">When you'd like your first installment to be due. Usually the start of next month.</p>
                @error('first_emi_date') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>

            {{-- 5. Purpose --}}
            <div>
                <label class="lmt-label">Purpose <span class="text-red-500">*</span></label>
                <textarea name="purpose" required minlength="10" maxlength="1000"
                          class="lmt-textarea" rows="3"
                          placeholder="Explain what this loan is for…">{{ old('purpose') }}</textarea>
                <p class="lmt-help">10–1000 characters.</p>
                @error('purpose') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>

            {{-- 6. Guarantor (shown only when loan type requires it) --}}
            <div x-show="selectedType?.requires_guarantor" x-cloak class="space-y-4">
                <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"></i>
                    <p class="text-xs text-amber-800 dark:text-amber-200">This loan type requires a guarantor. Please provide their details below.</p>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lmt-label">Guarantor name</label>
                        <input type="text" name="guarantor_external_name" maxlength="200"
                               class="lmt-input"
                               placeholder="Full name"
                               value="{{ old('guarantor_external_name') }}"/>
                    </div>
                    <div>
                        <label class="lmt-label">Guarantor phone</label>
                        <input type="text" name="guarantor_external_phone" maxlength="50"
                               class="lmt-input"
                               placeholder="+92 300 1234567"
                               value="{{ old('guarantor_external_phone') }}"/>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-slate-700">
                <a href="{{ route('employee.loans.index', $tenantSlug) }}" class="lmt-btn-secondary">Cancel</a>
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit Application
                </button>
            </div>
        </form>
    @endif
</div>

@php
    $loanTypesJson = $types->map(fn($t) => [
        'id'                    => $t->id,
        'name'                  => $t->name,
        'default_interest_rate' => (float) ($t->default_interest_rate ?? 0),
        'min_tenure_months'     => (int) ($t->min_tenure_months ?? 1),
        'max_tenure_months'     => (int) ($t->max_tenure_months ?? 120),
        'min_amount'            => (float) ($t->min_amount ?? 0),
        'requires_guarantor'    => (bool) $t->requires_guarantor,
        '_effective_max'        => $t->_effective_max ? (float) $t->_effective_max : null,
    ])->values();
@endphp
@push('scripts')
<script>
function loanApply() {
    return {
        form: {
            loan_type_id: {{ old('loan_type_id', 'null') ?: 'null' }},
            principal_amount: {{ old('principal_amount') ? (float) old('principal_amount') : 'null' }},
            tenure_months: {{ old('tenure_months') ? (int) old('tenure_months') : 'null' }},
        },
        types: @json($loanTypesJson),
        emi: { emi: 0, total_interest: 0, total_amount: 0 },
        hasEmiData: false,

        get selectedType() {
            return this.types.find(t => t.id === this.form.loan_type_id);
        },

        init() {
            this.$watch('form.loan_type_id', () => this.recalc());
            this.$watch('form.principal_amount', () => this.recalc());
            this.$watch('form.tenure_months', () => this.recalc());
            this.recalc();
        },

        onTypeChange(id) {
            this.form.loan_type_id = id;
            // Snap tenure to allowed range if necessary
            const t = this.selectedType;
            if (t && this.form.tenure_months) {
                if (this.form.tenure_months < t.min_tenure_months) this.form.tenure_months = t.min_tenure_months;
                if (this.form.tenure_months > t.max_tenure_months) this.form.tenure_months = t.max_tenure_months;
            }
            this.recalc();
        },

        recalc() {
            const type = this.selectedType;
            const p = this.form.principal_amount;
            const n = this.form.tenure_months;
            if (!type || !p || !n || p <= 0 || n <= 0) {
                this.hasEmiData = false;
                return;
            }
            const annualRate = type.default_interest_rate;
            let emi, total, interest;
            if (annualRate > 0) {
                const r = annualRate / 100 / 12;
                emi = p * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
                total = emi * n;
                interest = total - p;
            } else {
                emi = p / n;
                total = p;
                interest = 0;
            }
            this.emi = {
                emi: Math.round(emi * 100) / 100,
                total_amount: Math.round(total * 100) / 100,
                total_interest: Math.round(interest * 100) / 100,
            };
            this.hasEmiData = true;
        },

        formatNumber(v) {
            return Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatMoney(v) {
            if (v == null) return '';
            return '{{ $sym }}' + this.formatNumber(v);
        },
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection