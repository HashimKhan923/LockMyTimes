@extends('layouts.superadmin')
@section('title','Create Plan')
@section('page-title','Create Plan')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('superadmin.plans.index') }}"
       class="w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 text-ink-soft"></i>
    </a>
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Create Subscription Plan</h2>
        <p class="text-sm text-ink-soft">Configure pricing, modules, and limits.</p>
    </div>
</div>

<form action="{{ route('superadmin.plans.store') }}" method="POST">
    @csrf
    @include('superadmin.plans._form')
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
