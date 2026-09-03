@extends('layouts.auth')
@section('title','Reset Password')

@section('auth-panel')
<div class="text-white">
    <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-6">
        <i data-lucide="key" class="w-8 h-8 text-white"></i>
    </div>
    <h2 class="text-3xl font-black mb-3" style="font-family:'Nunito',sans-serif">Choose a new password</h2>
    <p class="text-white/75 text-sm leading-relaxed">
        Pick something strong and memorable — you'll use it every time you sign in.
    </p>
</div>
@endsection

@section('form')
<div class="mb-8">
    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center mb-4">
        <i data-lucide="key" class="w-6 h-6 text-emerald-600"></i>
    </div>
    <h1 class="text-2xl font-black text-ink mb-2" style="font-family:'Nunito',sans-serif">Reset your password</h1>
    <p class="text-ink-soft text-sm">Enter a new password for your account.</p>
</div>

@if($errors->any())
<div class="lmt-alert lmt-alert-error mb-6">
    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
    <span class="text-sm">{{ $errors->first() }}</span>
</div>
@endif

@if(session('error'))
<div class="lmt-alert lmt-alert-error mb-6">
    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
    <span class="text-sm">{{ session('error') }}</span>
</div>
@endif

<form action="{{ route('employee.password.reset.post', $tenantModel->slug) }}" method="POST" class="space-y-5">
    @csrf

    <div>
        <label class="lmt-label">Reset Code</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="hash" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input type="text" name="token" value="{{ old('token', $token) }}"
                   class="lmt-input pl-10 tracking-[0.3em] font-mono @error('token') lmt-input-error @enderror"
                   placeholder="123456" inputmode="numeric" pattern="\d{6}" maxlength="6" required/>
        </div>
        <p class="lmt-help">The 6-digit code from your email.</p>
        @error('token')
        <p class="lmt-err">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="lmt-label">Email</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="mail" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input type="email" name="email" value="{{ old('email', $email) }}"
                   class="lmt-input pl-10" placeholder="you@company.com" required/>
        </div>
    </div>

    <div>
        <label class="lmt-label">New Password</label>
        <div class="relative" x-data="{ show: false }">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="lock" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input :type="show ? 'text' : 'password'" name="password"
                   class="lmt-input pl-10 pr-10 @error('password') lmt-input-error @enderror"
                   placeholder="Min 8 characters" required/>
            <button type="button" @click="show=!show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-800">
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
                <i data-lucide="lock" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input type="password" name="password_confirmation"
                   class="lmt-input pl-10" placeholder="Repeat your password" required/>
        </div>
    </div>

    <button type="submit" class="lmt-btn-primary w-full lmt-btn-lg" style="background:linear-gradient(135deg,#10B981,#059669);">
        <i data-lucide="check" class="w-5 h-5"></i>
        Reset Password
    </button>
</form>

<div class="mt-6 text-center">
    <a href="{{ route('employee.login', $tenantModel->slug) }}"
       class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors inline-flex items-center gap-1.5">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
        Back to sign in
    </a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 100);
});
</script>
@endpush
