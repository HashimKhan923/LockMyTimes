@extends('layouts.employee')

@section('title', 'My Team')
@section('page-title', 'My Team')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="lmt-card text-center py-16 px-5" data-lmt-anim="fade-up">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-4">
            <i data-lucide="users-round" class="w-10 h-10 text-gray-800"></i>
        </div>
        <h1 class="text-xl font-black text-gray-900 dark:text-slate-100">No direct reports</h1>
        <p class="text-sm text-gray-800 mt-2 max-w-md mx-auto">
            You don't currently manage anyone on this team. If you think this is wrong, please contact HR to update your reporting structure.
        </p>
        <a href="{{ route('employee.dashboard', $tenantSlug) }}" class="lmt-btn-primary mt-6 inline-flex">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to dashboard
        </a>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection