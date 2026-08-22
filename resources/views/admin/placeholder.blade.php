@extends('layouts.admin')
@section('title', $title)
@section('page-title', $title)

@section('content')
<div class="lmt-card text-center py-20">
    <div class="w-16 h-16 rounded-2xl lmt-gradient-bg flex items-center justify-center mx-auto mb-4 opacity-30">
        <i data-lucide="construction" class="w-8 h-8 text-white"></i>
    </div>
    <h3 class="text-xl font-black text-gray-900 mb-2">{{ $title }}</h3>
    <p class="text-gray-800 text-sm max-w-sm mx-auto">
        This module is being built. It will be fully functional in an upcoming phase.
    </p>
    <a href="{{ route('admin.dashboard', $tenantSlug) }}"
       class="lmt-btn-secondary lmt-btn-sm inline-flex mt-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Dashboard
    </a>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush