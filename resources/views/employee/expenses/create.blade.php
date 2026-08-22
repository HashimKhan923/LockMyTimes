@extends('layouts.employee')

@section('title', 'Submit Expense')
@section('page-title', 'Submit Expense')

@section('content')
<div class="max-w-3xl mx-auto">

    @php
        $cur = $defaultCurrency ?? ($tenantCurrency ?? 'USD');
        $sym = match($cur) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY', 'CNY' => '¥',
            'INR' => '₹',
            'PKR' => 'Rs',
            'AED' => 'د.إ',
            'SAR' => '﷼',
            default => '',
        };
    @endphp

    {{-- Back link --}}
    <a href="{{ route('employee.expenses.index', $tenantSlug) }}"
       class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-800 hover:text-gray-700 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to expenses
    </a>

    <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
            Submit an Expense
        </h1>
        <p class="text-sm text-gray-800 mt-1">Submit a new expense claim. Your manager will be notified for approval.</p>
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

    <form action="{{ route('employee.expenses.store', $tenantSlug) }}" method="POST" enctype="multipart/form-data"
          class="lmt-card space-y-6"
          x-data="expenseForm()" x-init="init()">
        @csrf

        {{-- 1. Category --}}
        <div>
            <label class="lmt-label">Category <span class="text-red-500">*</span></label>
            @if($categories->isEmpty())
                <div class="lmt-alert lmt-alert-warning">
                    <i data-lucide="info" class="w-5 h-5 shrink-0"></i>
                    <p>No expense categories have been set up yet. Please contact your administrator.</p>
                </div>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                    @foreach($categories as $c)
                        @php
                            $perLimit = $c->per_expense_limit ?? null;
                            $reqReceipt = (bool) ($c->requires_receipt ?? false);
                            $reqAbove = $c->receipt_required_above ?? null;
                        @endphp
                        <label class="block cursor-pointer">
                            <input type="radio" name="category_id" value="{{ $c->id }}"
                                   class="sr-only"
                                   x-model.number="form.category_id"
                                   @change="onCategoryChange({{ $c->id }})"
                                   {{ (int) old('category_id') === (int) $c->id ? 'checked' : '' }}
                                   required/>
                            <div class="p-3 rounded-2xl border-2 transition-all"
                                 :class="form.category_id === {{ $c->id }} ? 'border-brand-500 bg-brand-50/40' : 'border-gray-200 hover:border-gray-300 dark:border-slate-700 dark:hover:border-slate-600'">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="{{ $c->icon ?? 'tag' }}" class="w-4 h-4 text-gray-800"></i>
                                    <span class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $c->name }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @if($reqReceipt)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 uppercase">
                                            Receipt @if($reqAbove && $reqAbove > 0) > {{ $sym }}{{ number_format($reqAbove, 0) }} @endif
                                        </span>
                                    @endif
                                    @if($perLimit)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 uppercase">
                                            Max {{ $sym }}{{ number_format($perLimit, 0) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
            @error('category_id') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        {{-- 2. Title --}}
        <div>
            <label class="lmt-label">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" required minlength="3" maxlength="200"
                   class="lmt-input"
                   placeholder="e.g. Client lunch at The Continental"
                   value="{{ old('title') }}"/>
            @error('title') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        {{-- 3. Amount + Date + Currency --}}
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="lmt-label">Amount <span class="text-red-500">*</span></label>
                <div class="relative">
                    @if($sym)
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-800 pointer-events-none">{{ $sym }}</span>
                    @endif
                    <input type="number" name="amount" required min="0.01" max="9999999.99" step="0.01"
                           class="lmt-input @if($sym) pl-8 @endif font-mono"
                           x-model.number="form.amount"
                           placeholder="0.00"
                           value="{{ old('amount') }}"/>
                </div>
                <p x-show="warning" x-text="warning" class="lmt-err mt-1"></p>
                @error('amount') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="lmt-label">Currency</label>
                <input type="text" name="currency" maxlength="3" minlength="3"
                       class="lmt-input font-mono uppercase"
                       placeholder="USD"
                       value="{{ old('currency', $defaultCurrency) }}"/>
                @error('currency') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="lmt-label">Expense date <span class="text-red-500">*</span></label>
                <input type="date" name="expense_date" required
                       max="{{ now()->toDateString() }}"
                       class="lmt-input"
                       value="{{ old('expense_date', now()->toDateString()) }}"/>
                <p class="lmt-help">Must be today or earlier.</p>
                @error('expense_date') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="lmt-label">Payment method <span class="text-gray-800 font-normal">(optional)</span></label>
                <select name="payment_method" class="lmt-input">
                    <option value="">— Select —</option>
                    @foreach(['Personal Card', 'Personal Cash', 'Company Card', 'Bank Transfer', 'Other'] as $pm)
                        <option value="{{ $pm }}" {{ old('payment_method') === $pm ? 'selected' : '' }}>{{ $pm }}</option>
                    @endforeach
                </select>
                @error('payment_method') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- 4. Merchant + Project --}}
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="lmt-label">Merchant <span class="text-gray-800 font-normal">(optional)</span></label>
                <input type="text" name="merchant" maxlength="200"
                       class="lmt-input"
                       placeholder="e.g. Starbucks, Uber"
                       value="{{ old('merchant') }}"/>
                @error('merchant') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="lmt-label">Project code <span class="text-gray-800 font-normal">(optional)</span></label>
                <input type="text" name="project_code" maxlength="50"
                       class="lmt-input font-mono"
                       placeholder="e.g. PROJ-2026-001"
                       value="{{ old('project_code') }}"/>
                @error('project_code') <p class="lmt-err">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- 5. Mileage toggle --}}
        <div>
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-2xl border-2 border-gray-200 dark:border-slate-700 hover:border-gray-300 transition-all"
                   :class="form.is_mileage ? 'border-brand-500 bg-brand-50/40' : ''">
                <input type="checkbox" name="is_mileage" value="1" x-model="form.is_mileage"
                       {{ old('is_mileage') ? 'checked' : '' }}
                       class="w-4 h-4 rounded cursor-pointer"
                       style="accent-color:var(--brand-500);"/>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm text-gray-900 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="car" class="w-4 h-4"></i>
                        Mileage claim
                    </p>
                    <p class="text-xs text-gray-800 mt-0.5">Tick this if you're claiming mileage instead of receipted expenses.</p>
                </div>
            </label>

            <div x-show="form.is_mileage" x-cloak x-transition class="grid sm:grid-cols-2 gap-4 mt-3 pl-3">
                <div>
                    <label class="lmt-label">Miles driven</label>
                    <input type="number" name="miles" min="0" max="99999" step="0.1"
                           class="lmt-input font-mono"
                           placeholder="0.0"
                           value="{{ old('miles') }}"/>
                </div>
                <div>
                    <label class="lmt-label">Rate per mile</label>
                    <div class="relative">
                        @if($sym)
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-800 pointer-events-none">{{ $sym }}</span>
                        @endif
                        <input type="number" name="mileage_rate" min="0" max="99" step="0.0001"
                               class="lmt-input @if($sym) pl-8 @endif font-mono"
                               placeholder="0.6700"
                               value="{{ old('mileage_rate') }}"/>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. Description --}}
        <div>
            <label class="lmt-label">Description <span class="text-gray-800 font-normal">(optional)</span></label>
            <textarea name="description" maxlength="1000"
                      class="lmt-textarea" rows="3"
                      placeholder="Anything that helps the approver understand this expense…">{{ old('description') }}</textarea>
            <p class="lmt-help">Max 1000 characters.</p>
            @error('description') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        {{-- 7. Receipt --}}
        <div>
            <label class="lmt-label">
                Receipt
                <span class="text-gray-800 font-normal" x-show="!selectedCategory?.requires_receipt">(recommended)</span>
                <span class="text-red-500" x-show="selectedCategory?.requires_receipt">*</span>
            </label>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"
                   class="block w-full text-sm text-gray-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-bold file:text-sm file:cursor-pointer
                          file:bg-gray-100 hover:file:bg-gray-200 dark:file:bg-slate-700 dark:hover:file:bg-slate-600 dark:file:text-slate-200"/>
            <p class="lmt-help">JPG, PNG, or PDF. Max 5 MB.</p>
            <p x-show="selectedCategory?.requires_receipt && selectedCategory?.receipt_required_above > 0" x-cloak class="lmt-help mt-1">
                Receipts are required for this category for amounts above
                <span x-text="formatMoney(selectedCategory?.receipt_required_above)" class="font-bold"></span>.
            </p>
            @error('receipt') <p class="lmt-err">{{ $message }}</p> @enderror
        </div>

        {{-- Footer actions --}}
        <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-2 pt-2 border-t border-gray-100 dark:border-slate-700">
            <a href="{{ route('employee.expenses.index', $tenantSlug) }}" class="lmt-btn-secondary">Cancel</a>
            <div class="flex flex-col sm:flex-row gap-2">
                <button type="submit" name="action" value="draft" class="lmt-btn-secondary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save as draft
                </button>
                <button type="submit" name="action" value="submit" class="lmt-btn-primary">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Submit for approval
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function expenseForm() {
    return {
        form: {
            category_id: {{ old('category_id', 'null') ?: 'null' }},
            amount: {{ old('amount') ? (float) old('amount') : 'null' }},
            is_mileage: {{ old('is_mileage') ? 'true' : 'false' }},
        },
        categories: {{ Js::from($categories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'requires_receipt'        => (bool) ($c->requires_receipt ?? false),
            'receipt_required_above'  => (float) ($c->receipt_required_above ?? 0),
            'per_expense_limit'       => $c->per_expense_limit ? (float) $c->per_expense_limit : null,
        ])->values()) }},
        warning: '',

        get selectedCategory() {
            return this.categories.find(c => c.id === this.form.category_id);
        },

        init() {
            this.$watch('form.amount', () => this.validateAmount());
            this.$watch('form.category_id', () => this.validateAmount());
        },

        onCategoryChange(id) {
            this.form.category_id = id;
            this.validateAmount();
        },

        validateAmount() {
            this.warning = '';
            const cat = this.selectedCategory;
            if (!cat || !this.form.amount) return;
            if (cat.per_expense_limit && this.form.amount > cat.per_expense_limit) {
                this.warning = `Exceeds the per-expense limit of ${this.formatMoney(cat.per_expense_limit)} for ${cat.name}.`;
            }
        },

        formatMoney(v) {
            if (v == null) return '';
            return '{{ $sym }}' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection