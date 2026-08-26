@extends('layouts.employee')

@section('title', 'Request Salary Advance')
@section('page-title', 'Request Salary Advance')

@section('content')
<div class="max-w-3xl mx-auto">

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
    <a href="{{ route('employee.loans.index', ['tenant' => $tenantSlug, 'tab' => 'advances']) }}"
       class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-800 hover:text-gray-800 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to advances
    </a>

    <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
            Request a Salary Advance
        </h1>
        <p class="text-sm text-gray-800 mt-1">A quick advance against your salary, repaid via payroll deductions.</p>
    </div>

    @if($hasActiveAdvance)
        <div class="lmt-alert lmt-alert-warning mb-5">
            <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">You already have an active advance.</p>
                <p class="text-sm">You can only have one advance request open at a time. Please wait for it to complete before applying again.</p>
            </div>
        </div>
        <a href="{{ route('employee.loans.index', ['tenant' => $tenantSlug, 'tab' => 'advances']) }}" class="lmt-btn-secondary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to advances
        </a>
    @else

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

        @if($baseSalary > 0)
            <div class="lmt-card p-4 mb-5 flex items-center gap-3" data-lmt-anim="fade-up">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     style="background:var(--brand-50);color:var(--brand-600);">
                    <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Maximum advance</p>
                    <p class="text-sm font-black text-gray-900 dark:text-slate-100 font-mono">
                        {{ $sym }}{{ number_format($baseSalary, 2) }}
                    </p>
                    <p class="text-[10px] text-gray-800">Capped at one month's base salary</p>
                </div>
            </div>
        @endif

        <form action="{{ route('employee.loans.advance.store', $tenantSlug) }}" method="POST"
              class="lmt-card space-y-6"
              x-data="advanceForm()" x-init="init()">
            @csrf

            {{-- 1. Amount --}}
            <div>
                <label class="lmt-label">Advance amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-800 pointer-events-none">{{ $sym }}</span>
                    <input type="number" name="amount" required min="1" step="0.01"
                           @if($baseSalary > 0) max="{{ $baseSalary }}" @endif
                           class="lmt-input pl-8 font-mono"
                           x-model.number="form.amount"
                           placeholder="0.00"
                           value="{{ old('amount') }}"/>
                </div>
                @if($baseSalary > 0)
                    <p class="lmt-help">Cannot exceed your monthly salary of {{ $sym }}{{ number_format($baseSalary, 2) }}.</p>
                @endif
                @error('amount') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>

            {{-- 2. Repayment type --}}
            <div>
                <label class="lmt-label">Repayment <span class="text-red-500">*</span></label>
                <div class="grid sm:grid-cols-2 gap-2.5">
                    <label class="block cursor-pointer">
                        <input type="radio" name="repayment_type" value="one_time"
                               class="sr-only"
                               x-model="form.repayment_type"
                               {{ old('repayment_type', 'one_time') === 'one_time' ? 'checked' : '' }}
                               required/>
                        <div class="p-3 rounded-2xl border-2 transition-all"
                             :class="form.repayment_type === 'one_time' ? 'border-brand-500 bg-brand-50/40' : 'border-gray-200 hover:border-gray-300 dark:border-slate-700'">
                            <div class="flex items-center gap-2">
                                <i data-lucide="zap" class="w-4 h-4 text-gray-800"></i>
                                <span class="font-bold text-sm text-gray-900 dark:text-slate-100">One-time deduction</span>
                            </div>
                            <p class="text-[11px] text-gray-800 dark:text-slate-400 mt-1.5">Repay in full from your next payslip.</p>
                        </div>
                    </label>
                    <label class="block cursor-pointer">
                        <input type="radio" name="repayment_type" value="installments"
                               class="sr-only"
                               x-model="form.repayment_type"
                               {{ old('repayment_type') === 'installments' ? 'checked' : '' }}
                               required/>
                        <div class="p-3 rounded-2xl border-2 transition-all"
                             :class="form.repayment_type === 'installments' ? 'border-brand-500 bg-brand-50/40' : 'border-gray-200 hover:border-gray-300 dark:border-slate-700'">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar-days" class="w-4 h-4 text-gray-800"></i>
                                <span class="font-bold text-sm text-gray-900 dark:text-slate-100">Spread over installments</span>
                            </div>
                            <p class="text-[11px] text-gray-800 dark:text-slate-400 mt-1.5">Repay across multiple payslips (max 12).</p>
                        </div>
                    </label>
                </div>
                @error('repayment_type') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>

            {{-- 3. Installments count (shown only if installments selected) --}}
            <div x-show="form.repayment_type === 'installments'" x-cloak>
                <label class="lmt-label">Number of installments <span class="text-red-500">*</span></label>
                <input type="number" name="installments_count" min="1" max="12" step="1"
                       class="lmt-input font-mono"
                       x-model.number="form.installments_count"
                       placeholder="1"
                       value="{{ old('installments_count', 1) }}"/>
                <p class="lmt-help">Between 1 and 12 monthly installments.</p>
                @error('installments_count') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>

            {{-- Live preview --}}
            <div x-show="hasPreview" x-cloak
                 class="rounded-2xl p-4"
                 style="background:linear-gradient(135deg,var(--brand-50),#ede9fe);">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">Per-installment deduction</p>
                        <p class="text-xl font-black font-mono mt-1" style="color:var(--brand-600);">
                            {{ $sym }}<span x-text="formatNumber(perInstallment)"></span>
                        </p>
                    </div>
                    <p class="text-xs text-gray-800 dark:text-slate-300">
                        <span class="font-bold" x-text="installmentsCount"></span> ×
                        {{ $sym }}<span x-text="formatNumber(perInstallment)"></span> =
                        {{ $sym }}<span x-text="formatNumber(form.amount)"></span>
                    </p>
                </div>
            </div>

            {{-- 4. Reason --}}
            <div>
                <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" required minlength="10" maxlength="1000"
                          class="lmt-textarea" rows="3"
                          placeholder="Briefly explain why you need this advance…">{{ old('reason') }}</textarea>
                <p class="lmt-help">10–1000 characters.</p>
                @error('reason') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>

            {{-- Footer --}}
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-slate-700">
                <a href="{{ route('employee.loans.index', ['tenant' => $tenantSlug, 'tab' => 'advances']) }}" class="lmt-btn-secondary">Cancel</a>
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit Request
                </button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
function advanceForm() {
    return {
        form: {
            amount: {{ old('amount') ? (float) old('amount') : 'null' }},
            repayment_type: '{{ old('repayment_type', 'one_time') }}',
            installments_count: {{ (int) old('installments_count', 1) }},
        },

        get installmentsCount() {
            return this.form.repayment_type === 'installments' ? (this.form.installments_count || 1) : 1;
        },
        get perInstallment() {
            if (!this.form.amount || this.form.amount <= 0) return 0;
            return Math.round((this.form.amount / this.installmentsCount) * 100) / 100;
        },
        get hasPreview() {
            return this.form.amount > 0;
        },

        init() {},

        formatNumber(v) {
            if (v == null || isNaN(v)) return '0.00';
            return Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection