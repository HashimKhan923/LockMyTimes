@extends('layouts.auth')

@section('title', 'Super Admin Login')

@section('auth-panel')
<div class="text-white">
    <div class="inline-flex items-center gap-2 bg-white/15 border border-white/25 rounded-full px-4 py-2 text-xs font-semibold mb-6">
        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
        Super Admin Access
    </div>
    <h2 class="text-3xl font-black mb-4 leading-tight" style="font-family:'Nunito',sans-serif">
        Platform Control Center
    </h2>
    <p class="text-white/75 mb-8 leading-relaxed">
        Manage all organizations, subscriptions, and platform settings from one place.
    </p>

    <div class="space-y-4">
        @foreach([
            ['icon'=>'building-2','label'=>'Manage all organizations'],
            ['icon'=>'credit-card','label'=>'Subscription & billing oversight'],
            ['icon'=>'bar-chart-2','label'=>'Platform analytics'],
        ] as $item)
        <div class="flex items-center gap-3 text-white/80">
            <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-white"></i>
            </div>
            <span class="text-sm font-medium">{{ $item['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endsection

@section('form')
<div class="mb-8">
    <h1 class="text-2xl font-black text-ink mb-2" style="font-family:'Nunito',sans-serif">
        Welcome back
    </h1>
    <p class="text-ink-soft text-sm">Sign in to your super admin account</p>
</div>

{{-- Validation Errors --}}
@if($errors->any())
<div class="lmt-alert lmt-alert-error mb-6">
    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
    <span class="text-sm">{{ $errors->first() }}</span>
</div>
@endif

<form action="{{ route('superadmin.login.post') }}" method="POST" class="space-y-5">
    @csrf

    <div>
        <label class="lmt-label">Email Address</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="mail" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="lmt-input pl-10 @error('email') lmt-input-error @enderror"
                   placeholder="admin@lockmytimes.com" autofocus required/>
        </div>
    </div>

    <div>
        <label class="lmt-label">Password</label>
        <div class="relative" x-data="{ show: false }">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="lock" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input :type="show ? 'text' : 'password'" name="password"
                   class="lmt-input pl-10 pr-10 @error('password') lmt-input-error @enderror"
                   placeholder="••••••••" required/>
            <button type="button" @click="show=!show"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-800 hover:text-gray-800">
                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"/>
            <span class="text-sm text-gray-800">Remember me</span>
        </label>
    </div>

    <button type="submit" class="lmt-btn-primary w-full lmt-btn-lg">
        <i data-lucide="log-in" class="w-5 h-5"></i>
        Sign In to Control Center
    </button>
</form>

<div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
    <div class="flex items-start gap-3">
        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
        <p class="text-xs text-amber-700">
            This is the platform owner portal. If you're a company admin or employee, use your company's dedicated URL instead.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    // Re-init after Alpine renders password toggle
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 100);
});
</script>
@endpush