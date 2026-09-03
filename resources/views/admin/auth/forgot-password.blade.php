@extends('layouts.auth')
@section('title','Forgot Password')

@section('auth-panel')
<div class="text-white">
    <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-6">
        <i data-lucide="mail-question" class="w-8 h-8 text-white"></i>
    </div>
    <h2 class="text-3xl font-black mb-3" style="font-family:'Nunito',sans-serif">Forgot your password?</h2>
    <p class="text-white/75 text-sm leading-relaxed">
        No problem — enter the email address on your admin account and we'll send you a link to reset it.
    </p>
</div>
@endsection

@section('form')
<div class="mb-8">
    <div class="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center mb-4">
        <i data-lucide="mail-question" class="w-6 h-6 text-brand-600"></i>
    </div>
    <h1 class="text-2xl font-black text-ink mb-2" style="font-family:'Nunito',sans-serif">Reset your password</h1>
    <p class="text-ink-soft text-sm">Enter your email and we'll send you a reset link.</p>
</div>

@if($errors->any())
<div class="lmt-alert lmt-alert-error mb-6">
    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
    <span class="text-sm">{{ $errors->first() }}</span>
</div>
@endif

@if(session('success'))
<div class="lmt-alert lmt-alert-success mb-6">
    <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
    <span class="text-sm">{{ session('success') }}</span>
</div>
@endif

<form action="{{ route('admin.password.forgot.post', $tenantModel->slug) }}" method="POST" class="space-y-5">
    @csrf

    <div>
        <label class="lmt-label">Work Email</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="mail" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="lmt-input pl-10" placeholder="you@company.com" autofocus required/>
        </div>
    </div>

    <button type="submit" class="lmt-btn-primary w-full lmt-btn-lg">
        <i data-lucide="send" class="w-5 h-5"></i>
        Send Reset Link
    </button>
</form>

<div class="mt-6 text-center">
    <a href="{{ route('admin.login', $tenantModel->slug) }}"
       class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors inline-flex items-center gap-1.5">
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
