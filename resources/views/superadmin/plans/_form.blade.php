{{-- Shared form partial for create/edit --}}
<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left: Main settings --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Basic Info --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-5" style="font-family:'Nunito',sans-serif">Plan Details</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="lmt-label">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}"
                           class="lmt-input" placeholder="e.g. Professional" required
                           oninput="autoSlug(this.value)">
                    @error('name')<p class="lmt-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lmt-label">Slug</label>
                    <input type="text" name="slug" id="slug-field" value="{{ old('slug', $plan->slug ?? '') }}"
                           class="lmt-input font-mono text-sm" placeholder="auto-generated">
                    @error('slug')<p class="lmt-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lmt-label">Badge Label</label>
                    <input type="text" name="badge" value="{{ old('badge', $plan->badge ?? '') }}"
                           class="lmt-input" placeholder="e.g. Most Popular">
                    @error('badge')<p class="lmt-error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="lmt-label">Description</label>
                    <textarea name="description" class="lmt-textarea" rows="2"
                              placeholder="Short plan description shown to customers…">{{ old('description', $plan->description ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-5" style="font-family:'Nunito',sans-serif">Pricing</h3>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="lmt-label">Currency <span class="text-red-500">*</span></label>
                    <select name="currency" class="lmt-select">
                        @foreach(['USD','EUR','GBP','CAD','AUD'] as $c)
                        <option value="{{ $c }}" {{ old('currency', $plan->currency ?? 'USD') === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Monthly Price <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-ink-soft text-sm">$</span>
                        <input type="number" name="monthly_price" step="0.01" min="0"
                               value="{{ old('monthly_price', $plan->monthly_price ?? '') }}"
                               class="lmt-input pl-7" placeholder="0.00" required>
                    </div>
                    @error('monthly_price')<p class="lmt-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lmt-label">Yearly Price <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-ink-soft text-sm">$</span>
                        <input type="number" name="yearly_price" step="0.01" min="0"
                               value="{{ old('yearly_price', $plan->yearly_price ?? '') }}"
                               class="lmt-input pl-7" placeholder="0.00" required>
                    </div>
                    @error('yearly_price')<p class="lmt-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Limits --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-5" style="font-family:'Nunito',sans-serif">Usage Limits</h3>
            <p class="text-xs text-ink-soft mb-4">Leave blank for unlimited.</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="lmt-label">Max Employees</label>
                    <input type="number" name="max_employees" min="1"
                           value="{{ old('max_employees', $plan->max_employees ?? '') }}"
                           class="lmt-input" placeholder="Unlimited">
                </div>
                <div>
                    <label class="lmt-label">Max Admins</label>
                    <input type="number" name="max_admins" min="1"
                           value="{{ old('max_admins', $plan->max_admins ?? '') }}"
                           class="lmt-input" placeholder="Unlimited">
                </div>
                <div>
                    <label class="lmt-label">Storage (GB)</label>
                    <input type="number" name="max_storage_gb" min="1"
                           value="{{ old('max_storage_gb', $plan->max_storage_gb ?? '') }}"
                           class="lmt-input" placeholder="Unlimited">
                </div>
            </div>
        </div>

        {{-- Stripe IDs --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-1" style="font-family:'Nunito',sans-serif">Stripe Integration</h3>
            <p class="text-xs text-ink-soft mb-5">Link this plan to your Stripe products and prices.</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="lmt-label">Stripe Product ID</label>
                    <input type="text" name="stripe_product_id"
                           value="{{ old('stripe_product_id', $plan->stripe_product_id ?? '') }}"
                           class="lmt-input font-mono text-sm" placeholder="prod_...">
                </div>
                <div>
                    <label class="lmt-label">Monthly Price ID</label>
                    <input type="text" name="stripe_monthly_price_id"
                           value="{{ old('stripe_monthly_price_id', $plan->stripe_monthly_price_id ?? '') }}"
                           class="lmt-input font-mono text-sm" placeholder="price_...">
                </div>
                <div>
                    <label class="lmt-label">Yearly Price ID</label>
                    <input type="text" name="stripe_yearly_price_id"
                           value="{{ old('stripe_yearly_price_id', $plan->stripe_yearly_price_id ?? '') }}"
                           class="lmt-input font-mono text-sm" placeholder="price_...">
                </div>
            </div>
        </div>

        {{-- Custom Features --}}
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Custom Feature List</h3>
                <button type="button" onclick="addFeature()" class="lmt-btn-ghost lmt-btn-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Feature
                </button>
            </div>
            <div id="features-list" class="space-y-2">
                @php $features = old('features', $plan->features ?? []); @endphp
                @forelse($features as $feat)
                <div class="feature-row flex items-center gap-2">
                    <input type="text" name="features[]" value="{{ $feat }}"
                           class="lmt-input text-sm flex-1" placeholder="Feature description…">
                    <button type="button" onclick="this.closest('.feature-row').remove()"
                            class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                @empty
                <p class="text-sm text-ink-soft text-center py-4 border-2 border-dashed border-gray-200 rounded-xl" id="features-empty">
                    No custom features yet. Click "Add Feature" to start.
                </p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right: Flags & Options --}}
    <div class="space-y-6">

        {{-- Status --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Plan Options</h3>
            <div class="space-y-4">
                <div>
                    <label class="lmt-label">Sort Order</label>
                    <input type="number" name="sort_order" min="0"
                           value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
                           class="lmt-input" placeholder="0">
                </div>
                <div>
                    <label class="lmt-label">Trial Days</label>
                    <input type="number" name="trial_days" min="0"
                           value="{{ old('trial_days', $plan->trial_days ?? 14) }}"
                           class="lmt-input" placeholder="14">
                </div>

                @foreach([
                    ['name' => 'is_active',   'label' => 'Active',   'desc' => 'Make this plan available for signup'],
                    ['name' => 'is_featured', 'label' => 'Featured', 'desc' => 'Highlight this plan on pricing page'],
                ] as $opt)
                <label class="flex items-start gap-3 cursor-pointer group">
                    <div class="relative mt-0.5">
                        <input type="hidden" name="{{ $opt['name'] }}" value="0">
                        <input type="checkbox" name="{{ $opt['name'] }}" value="1"
                               {{ old($opt['name'], $plan->{$opt['name']} ?? false) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-200 rounded-full peer-checked:bg-brand-500 transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-ink">{{ $opt['label'] }}</p>
                        <p class="text-xs text-ink-soft">{{ $opt['desc'] }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Module toggles --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Included Modules</h3>
            <div class="space-y-3">
                @foreach([
                    ['name' => 'has_attendance',       'label' => 'Attendance & Shifts',  'icon' => 'clock'],
                    ['name' => 'has_payroll',           'label' => 'Payroll',              'icon' => 'banknote'],
                    ['name' => 'has_performance',       'label' => 'Performance Reviews',  'icon' => 'bar-chart-2'],
                    ['name' => 'has_recruitment',       'label' => 'Recruitment ATS',      'icon' => 'user-plus'],
                    {{-- 'has_documents' hidden per client request (2026-08) — column/model untouched --}}
                    ['name' => 'has_expenses',          'label' => 'Expenses',             'icon' => 'receipt'],
                    ['name' => 'has_assets',            'label' => 'Asset Management',     'icon' => 'package'],
                    ['name' => 'has_training',          'label' => 'Training',             'icon' => 'graduation-cap'],
                    ['name' => 'has_api_access',        'label' => 'API Access',           'icon' => 'code-2'],
                    ['name' => 'has_white_label',       'label' => 'White Label',          'icon' => 'paintbrush'],
                    ['name' => 'has_priority_support',  'label' => 'Priority Support',     'icon' => 'headphones'],
                ] as $mod)
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="hidden" name="{{ $mod['name'] }}" value="0">
                    <input type="checkbox" name="{{ $mod['name'] }}" value="1"
                           {{ old($mod['name'], $plan->{$mod['name']} ?? false) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <i data-lucide="{{ $mod['icon'] }}" class="w-4 h-4 text-ink-soft flex-shrink-0"></i>
                    <span class="text-sm font-medium text-ink">{{ $mod['label'] }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex flex-col gap-3">
            <button type="submit" class="lmt-btn-primary w-full justify-center">
                <i data-lucide="save" class="w-4 h-4"></i>
                {{ isset($plan->id) ? 'Save Changes' : 'Create Plan' }}
            </button>
            <a href="{{ route('superadmin.plans.index') }}" class="lmt-btn-secondary w-full justify-center">
                Cancel
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function autoSlug(name) {
    const field = document.getElementById('slug-field');
    if (!field._userEdited) {
        field.value = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
}
document.getElementById('slug-field').addEventListener('input', function () {
    this._userEdited = true;
});

function addFeature() {
    const empty = document.getElementById('features-empty');
    if (empty) empty.remove();
    const row = document.createElement('div');
    row.className = 'feature-row flex items-center gap-2';
    row.innerHTML = `
        <input type="text" name="features[]" class="lmt-input text-sm flex-1" placeholder="Feature description…">
        <button type="button" onclick="this.closest('.feature-row').remove()"
                class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>`;
    document.getElementById('features-list').appendChild(row);
    if (window.lucide) lucide.createIcons();
}
</script>
@endpush
