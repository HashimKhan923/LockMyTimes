@extends('layouts.auth')
@section('title','Set New Password')

@section('auth-panel')
<div class="text-white">
    <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-6">
        <i data-lucide="key" class="w-8 h-8 text-white"></i>
    </div>
    <h2 class="text-3xl font-black mb-3" style="font-family:'Nunito',sans-serif">Security first</h2>
    <p class="text-white/75 text-sm leading-relaxed">
        For your security, please set a new personal password before accessing your workspace. Choose something strong and memorable.
    </p>
</div>
@endsection

@section('form')
<div class="mb-8">
    <div class="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center mb-4">
        <i data-lucide="key" class="w-6 h-6 text-brand-600"></i>
    </div>
    <h1 class="text-2xl font-black text-ink mb-2" style="font-family:'Nunito',sans-serif">Set your password</h1>
    <p class="text-ink-soft text-sm">Choose a new password to secure your account.</p>
</div>

<form action="{{ route('admin.password.change.post', $tenantSlug) }}" method="POST" class="space-y-5">
    @csrf

    <div>
        <label class="lmt-label">New Password</label>
        <div class="relative" x-data="{ show: false }">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
            </div>
            <input :type="show ? 'text' : 'password'" name="password"
                   class="lmt-input pl-10 pr-10 @error('password') lmt-input-error @enderror"
                   placeholder="Min 8 characters" required/>
            <button type="button" @click="show=!show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400">
                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
            </button>
        </div>
        @error('password')
        <p class="lmt-err">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="lmt-label">Confirm Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
            </div>
            <input type="password" name="password_confirmation"
                   class="lmt-input pl-10" placeholder="Repeat your password" required/>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
        <p class="font-semibold mb-1">Password requirements:</p>
        <ul class="space-y-1 text-xs list-disc list-inside">
            <li>At least 8 characters</li>
            <li>Mix of letters and numbers recommended</li>
        </ul>
    </div>

    <button type="submit" class="lmt-btn-primary w-full lmt-btn-lg">
        <i data-lucide="check" class="w-5 h-5"></i>
        Set Password & Continue
    </button>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 100);
});
</script>
@endpush