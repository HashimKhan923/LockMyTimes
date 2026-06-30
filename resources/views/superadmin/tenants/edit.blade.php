@extends('layouts.superadmin')
@section('title','Edit — '.$tenant->company_name)
@section('page-title','Edit Tenant')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('superadmin.tenants.show', $tenant) }}"
       class="w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 text-ink-soft"></i>
    </a>
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Edit Tenant</h2>
        <p class="text-sm text-ink-soft">{{ $tenant->company_name }}</p>
    </div>
</div>

<form action="{{ route('superadmin.tenants.update', $tenant) }}" method="POST">
    @csrf @method('PUT')

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- Company info --}}
            <div class="lmt-card">
                <h3 class="font-bold text-ink mb-5" style="font-family:'Nunito',sans-serif">Company Information</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="lmt-label">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $tenant->company_name) }}"
                               class="lmt-input" required>
                        @error('company_name')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">Industry</label>
                        <input type="text" name="industry" value="{{ old('industry', $tenant->industry) }}"
                               class="lmt-input" placeholder="e.g. Technology">
                    </div>
                    <div>
                        <label class="lmt-label">Company Size</label>
                        <input type="number" name="company_size" min="1" value="{{ old('company_size', $tenant->company_size) }}"
                               class="lmt-input" placeholder="Approx. employee count">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="lmt-label">Website</label>
                        <input type="url" name="website" value="{{ old('website', $tenant->website) }}"
                               class="lmt-input" placeholder="https://...">
                    </div>
                </div>
            </div>

            {{-- Contact --}}
            <div class="lmt-card">
                <h3 class="font-bold text-ink mb-5" style="font-family:'Nunito',sans-serif">Primary Contact</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lmt-label">Contact Name <span class="text-red-500">*</span></label>
                        <input type="text" name="contact_name" value="{{ old('contact_name', $tenant->contact_name) }}"
                               class="lmt-input" required>
                        @error('contact_name')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">Contact Email <span class="text-red-500">*</span></label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $tenant->contact_email) }}"
                               class="lmt-input" required>
                        @error('contact_email')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">Phone</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $tenant->contact_phone) }}"
                               class="lmt-input" placeholder="+1 555 000 0000">
                    </div>
                </div>
            </div>

            {{-- Address --}}
            <div class="lmt-card">
                <h3 class="font-bold text-ink mb-5" style="font-family:'Nunito',sans-serif">Address</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="lmt-label">Street Address</label>
                        <input type="text" name="address_line1" value="{{ old('address_line1', $tenant->address_line1) }}"
                               class="lmt-input" placeholder="123 Main Street">
                    </div>
                    <div>
                        <label class="lmt-label">City</label>
                        <input type="text" name="city" value="{{ old('city', $tenant->city) }}" class="lmt-input">
                    </div>
                    <div>
                        <label class="lmt-label">State / Province</label>
                        <input type="text" name="state" value="{{ old('state', $tenant->state) }}" class="lmt-input">
                    </div>
                    <div>
                        <label class="lmt-label">Postal Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $tenant->postal_code) }}" class="lmt-input">
                    </div>
                    <div>
                        <label class="lmt-label">Country (ISO 2)</label>
                        <input type="text" name="country" value="{{ old('country', $tenant->country) }}"
                               class="lmt-input uppercase" maxlength="2" placeholder="US">
                    </div>
                </div>
            </div>
        </div>

        {{-- Right sidebar --}}
        <div class="space-y-5">

            {{-- Status + locale --}}
            <div class="lmt-card">
                <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Account Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="lmt-label">Status</label>
                        <select name="status" class="lmt-select">
                            @foreach(['pending','trial','active','past_due','suspended','cancelled'] as $s)
                            <option value="{{ $s }}" {{ old('status', $tenant->status)===$s?'selected':'' }}>
                                {{ ucfirst(str_replace('_',' ',$s)) }}
                            </option>
                            @endforeach
                        </select>
                        @error('status')<p class="lmt-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="lmt-label">Timezone</label>
                        <input type="text" name="timezone" value="{{ old('timezone', $tenant->timezone) }}"
                               class="lmt-input" placeholder="America/New_York">
                    </div>
                </div>
            </div>

            {{-- Read-only info --}}
            <div class="lmt-card bg-gray-50">
                <h3 class="font-bold text-ink mb-3 text-sm" style="font-family:'Nunito',sans-serif">Read-Only</h3>
                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-ink-soft">Slug</dt>
                        <dd class="font-mono text-ink">{{ $tenant->slug }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-soft">Database</dt>
                        <dd class="font-mono text-ink">{{ $tenant->database_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-soft">Joined</dt>
                        <dd class="text-ink">{{ $tenant->created_at->format('M j, Y') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-col gap-3">
                <button type="submit" class="lmt-btn-primary w-full justify-center">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                </button>
                <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="lmt-btn-secondary w-full justify-center">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });</script>
@endpush
