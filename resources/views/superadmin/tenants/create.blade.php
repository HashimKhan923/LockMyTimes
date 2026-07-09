@extends('layouts.superadmin')
@section('title','Create Organization')
@section('page-title','Create Organization')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('superadmin.organizations.index') }}"
       class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 text-gray-500"></i>
    </a>
    <div>
        <h2 class="text-xl font-black text-ink">Create New Organization</h2>
        <p class="text-sm text-ink-soft mt-0.5">Provision a new organization account and its dedicated database.</p>
    </div>
</div>

<form action="{{ route('superadmin.organizations.store') }}" method="POST" class="space-y-6 max-w-3xl">
    @csrf

    {{-- Company Info --}}
    <div class="lmt-card space-y-4">
        <h3 class="font-semibold text-ink flex items-center gap-2">
            <i data-lucide="building-2" class="w-4 h-4 text-brand-500"></i>
            Organization Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="lmt-label">Organization Name <span class="text-red-400">*</span></label>
                <input type="text" name="company_name" value="{{ old('company_name') }}"
                       class="lmt-input @error('company_name') border-red-400 @enderror"
                       placeholder="Acme Corp" required autofocus>
                @error('company_name')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="lmt-label">Industry</label>
                <select name="industry" class="lmt-input">
                    <option value="">— Select Industry —</option>
                    @foreach(['Technology','Healthcare','Finance','Retail','Manufacturing','Education','Hospitality','Construction','Logistics','Other'] as $ind)
                    <option value="{{ $ind }}" {{ old('industry') === $ind ? 'selected' : '' }}>{{ $ind }}</option>
                    @endforeach
                </select>
                @error('industry')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="lmt-label">Company Size (employees)</label>
                <input type="number" name="company_size" value="{{ old('company_size') }}"
                       class="lmt-input" placeholder="50" min="1">
                @error('company_size')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="lmt-label">Website</label>
                <input type="url" name="website" value="{{ old('website') }}"
                       class="lmt-input" placeholder="https://acme.com">
                @error('website')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- Contact Person --}}
    <div class="lmt-card space-y-4">
        <h3 class="font-semibold text-ink flex items-center gap-2">
            <i data-lucide="user" class="w-4 h-4 text-brand-500"></i>
            Primary Contact
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="lmt-label">Full Name <span class="text-red-400">*</span></label>
                <input type="text" name="contact_name" value="{{ old('contact_name') }}"
                       class="lmt-input @error('contact_name') border-red-400 @enderror"
                       placeholder="Jane Smith" required>
                @error('contact_name')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="lmt-label">Email Address <span class="text-red-400">*</span></label>
                <input type="email" name="contact_email" value="{{ old('contact_email') }}"
                       class="lmt-input @error('contact_email') border-red-400 @enderror"
                       placeholder="jane@acme.com" required>
                @error('contact_email')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="lmt-label">Phone</label>
                <input type="text" name="contact_phone" value="{{ old('contact_phone') }}"
                       class="lmt-input" placeholder="+1 555 000 0000">
                @error('contact_phone')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- Address --}}
    <div class="lmt-card space-y-4">
        <h3 class="font-semibold text-ink flex items-center gap-2">
            <i data-lucide="map-pin" class="w-4 h-4 text-brand-500"></i>
            Address &amp; Timezone
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="lmt-label">Address Line 1</label>
                <input type="text" name="address_line1" value="{{ old('address_line1') }}"
                       class="lmt-input" placeholder="123 Main Street">
                @error('address_line1')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="lmt-label">City</label>
                <input type="text" name="city" value="{{ old('city') }}"
                       class="lmt-input" placeholder="New York">
            </div>

            <div>
                <label class="lmt-label">State / Province</label>
                <input type="text" name="state" value="{{ old('state') }}"
                       class="lmt-input" placeholder="NY">
            </div>

            <div>
                <label class="lmt-label">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                       class="lmt-input" placeholder="10001">
            </div>

            <div>
                <label class="lmt-label">Country</label>
                <select name="country" class="lmt-input">
                    @php $countries = ['US'=>'United States','GB'=>'United Kingdom','CA'=>'Canada','AU'=>'Australia','IN'=>'India','DE'=>'Germany','FR'=>'France','AE'=>'UAE','SG'=>'Singapore','OTHER'=>'Other']; @endphp
                    @foreach($countries as $code => $name)
                    <option value="{{ $code }}" {{ (old('country','US') === $code) ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="lmt-label">Timezone</label>
                <select name="timezone" class="lmt-input">
                    @php $tzones = ['America/New_York'=>'Eastern (UTC-5)','America/Chicago'=>'Central (UTC-6)','America/Denver'=>'Mountain (UTC-7)','America/Los_Angeles'=>'Pacific (UTC-8)','America/Anchorage'=>'Alaska (UTC-9)','Pacific/Honolulu'=>'Hawaii (UTC-10)','Europe/London'=>'London (UTC+0)','Europe/Paris'=>'Paris (UTC+1)','Asia/Dubai'=>'Dubai (UTC+4)','Asia/Kolkata'=>'India (UTC+5:30)','Asia/Singapore'=>'Singapore (UTC+8)','Australia/Sydney'=>'Sydney (UTC+10)']; @endphp
                    @foreach($tzones as $tz => $label)
                    <option value="{{ $tz }}" {{ (old('timezone','America/New_York') === $tz) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Plan & Trial --}}
    <div class="lmt-card space-y-4">
        <h3 class="font-semibold text-ink flex items-center gap-2">
            <i data-lucide="credit-card" class="w-4 h-4 text-brand-500"></i>
            Subscription Plan
        </h3>

        @if($plans->isEmpty())
        <p class="text-sm text-ink-soft">No active plans found. The organization will be created without a subscription.</p>
        @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-3">
                <label class="lmt-label">Plan (optional)</label>
            </div>
            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50 transition-all">
                <input type="radio" name="plan_id" value="" class="mt-0.5 accent-brand-500" {{ old('plan_id') === '' || old('plan_id') === null ? 'checked' : '' }}>
                <div>
                    <p class="font-medium text-sm text-ink">No Plan</p>
                    <p class="text-xs text-ink-soft">Trial only, no subscription</p>
                </div>
            </label>
            @foreach($plans as $plan)
            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50 transition-all">
                <input type="radio" name="plan_id" value="{{ $plan->id }}" class="mt-0.5 accent-brand-500"
                       {{ old('plan_id') == $plan->id ? 'checked' : '' }}>
                <div>
                    <p class="font-medium text-sm text-ink">{{ $plan->name }}</p>
                    <p class="text-xs text-ink-soft">${{ number_format($plan->monthly_price, 0) }}/mo · ${{ number_format($plan->yearly_price, 0) }}/yr</p>
                </div>
            </label>
            @endforeach
        </div>

        <div>
            <label class="lmt-label">Billing Cycle</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="billing_cycle" value="monthly" class="accent-brand-500"
                           {{ old('billing_cycle','monthly') === 'monthly' ? 'checked' : '' }}>
                    <span class="text-sm text-ink">Monthly</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="billing_cycle" value="yearly" class="accent-brand-500"
                           {{ old('billing_cycle') === 'yearly' ? 'checked' : '' }}>
                    <span class="text-sm text-ink">Yearly</span>
                </label>
            </div>
        </div>
        @endif

        <div>
            <label class="lmt-label">Trial Period (days)</label>
            <input type="number" name="trial_days" value="{{ old('trial_days', 14) }}"
                   class="lmt-input w-32" min="0" max="365">
            <p class="lmt-help">Set to 0 to skip the trial and activate immediately.</p>
            @error('trial_days')<p class="lmt-err">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Admin Password --}}
    <div class="lmt-card space-y-4">
        <h3 class="font-semibold text-ink flex items-center gap-2">
            <i data-lucide="lock" class="w-4 h-4 text-brand-500"></i>
            Initial Admin Password
        </h3>
        <div>
            <label class="lmt-label">Password <span class="text-red-400">*</span></label>
            <input type="password" name="admin_password" class="lmt-input @error('admin_password') border-red-400 @enderror"
                   placeholder="Min 8 characters" required>
            <p class="lmt-help">The organization admin will use the contact email + this password to log in.</p>
            @error('admin_password')<p class="lmt-err">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
        <button type="submit" class="lmt-btn-primary lmt-btn-lg">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            Create Organization & Provision Database
        </button>
        <a href="{{ route('superadmin.organizations.index') }}" class="lmt-btn-secondary lmt-btn-lg">
            Cancel
        </a>
    </div>
</form>

@endsection
