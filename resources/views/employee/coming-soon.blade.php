@extends('layouts.employee')

@section('title', $feature . ' — Coming Soon')
@section('page-title', $feature)

@section('content')
<div class="max-w-2xl mx-auto py-8 lg:py-16">

    <div class="text-center mb-8">
        <div class="inline-flex w-20 h-20 rounded-3xl items-center justify-center mb-5"
             style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));box-shadow:0 12px 32px var(--brand-shadow-35);">
            <i data-lucide="hammer" class="w-9 h-9 text-white"></i>
        </div>
        <h1 class="text-3xl font-black text-gray-900 mb-2" style="font-family:'Plus Jakarta Sans',sans-serif">
            {{ $feature }} is on the way
        </h1>
        <p class="text-gray-500 max-w-md mx-auto">
            We're polishing this section to make it just right. Check back soon — it'll be worth the wait.
        </p>
    </div>

    <div class="lmt-card text-center py-8">
        <div class="flex items-center justify-center gap-2 mb-4">
            <span class="lmt-badge-amber">
                <i data-lucide="rocket" class="w-3 h-3"></i>
                In development
            </span>
        </div>
        <a href="{{ route('employee.dashboard', $tenantSlug) }}" class="lmt-btn-primary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to Home
        </a>
    </div>
</div>
@endsection