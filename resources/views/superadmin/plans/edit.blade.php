@extends('layouts.superadmin')
@section('title','Edit Plan — '.$plan->name)
@section('page-title','Edit Plan')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('superadmin.plans.index') }}"
       class="w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 text-ink-soft"></i>
    </a>
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Edit Plan: {{ $plan->name }}</h2>
        <p class="text-sm text-ink-soft">{{ $plan->subscriptions_count ?? 0 }} active subscriber(s).</p>
    </div>
</div>

<form action="{{ route('superadmin.plans.update', $plan) }}" method="POST">
    @csrf
    @method('PUT')
    @include('superadmin.plans._form')
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
