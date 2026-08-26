@extends('layouts.employee')

@section('title', $announcement->title)
@section('page-title', 'Announcement')

@section('content')
<div class="max-w-4xl mx-auto">

    @php
        [$priLbl, $priCls, $priIcon] = match($announcement->priority) {
            'urgent' => ['Urgent', 'text-red-600 bg-red-50 dark:bg-red-500/15 dark:text-red-300', 'alert-triangle'],
            'high'   => ['High',   'text-amber-600 bg-amber-50 dark:bg-amber-500/15 dark:text-amber-300', 'arrow-up'],
            'low'    => ['Low',    'text-gray-800 bg-gray-50 dark:bg-slate-700', 'arrow-down'],
            default  => ['Normal', 'text-gray-800 bg-gray-50 dark:bg-slate-700', 'minus'],
        };
    @endphp

    {{-- Back nav --}}
    <a href="{{ route('employee.announcements.index', $tenantSlug) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 dark:hover:text-slate-200 transition-colors mb-5">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>All announcements</span>
    </a>

    @if(session('success'))
        <div class="lmt-alert lmt-alert-success mb-5">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         ANNOUNCEMENT CARD
    ═══════════════════════════════════════════════════════════════ --}}
    <article class="lmt-card overflow-hidden" data-lmt-anim="fade-up">

        @if($announcement->banner_image)
            <div class="-m-5 lg:-m-6 mb-6 h-48 lg:h-64 bg-gray-100 dark:bg-slate-800 overflow-hidden">
                <img src="{{ asset('storage/' . $announcement->banner_image) }}" alt=""
                     class="w-full h-full object-cover"/>
            </div>
        @endif

        {{-- Badges --}}
        <div class="flex items-center gap-2 flex-wrap mb-3">
            @if($announcement->priority !== 'normal')
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase {{ $priCls }}">
                    <i data-lucide="{{ $priIcon }}" class="w-2.5 h-2.5"></i>
                    {{ $priLbl }} priority
                </span>
            @endif

            @if($announcement->requires_acknowledgment)
                @if($isAcknowledged)
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <i data-lucide="check-circle" class="w-2.5 h-2.5"></i>
                        Acknowledged
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                        <i data-lucide="check-square" class="w-2.5 h-2.5"></i>
                        Requires acknowledgment
                    </span>
                @endif
            @endif

            @if($announcement->expires_at)
                @if($announcement->expires_at->lte(now()->addDays(3)) && $announcement->expires_at->isFuture())
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300">
                        <i data-lucide="clock" class="w-2.5 h-2.5"></i>
                        Expires {{ $announcement->expires_at->diffForHumans() }}
                    </span>
                @endif
            @endif
        </div>

        {{-- Title --}}
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100 leading-tight"
            style="font-family:'Plus Jakarta Sans',sans-serif">
            {{ $announcement->title }}
        </h1>

        {{-- Meta --}}
        <div class="flex items-center gap-4 mt-3 pb-5 border-b border-gray-100 dark:border-slate-700 text-xs text-gray-800 flex-wrap">
            @if($announcement->creator)
                <span class="inline-flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black text-white" style="background:var(--brand-500);">
                        {{ strtoupper(substr($announcement->creator->name, 0, 1)) }}
                    </div>
                    <span>
                        Posted by <span class="font-bold text-gray-800 dark:text-slate-200">{{ $announcement->creator->name }}</span>
                    </span>
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3 h-3"></i>
                {{ ($announcement->publish_at ?? $announcement->created_at)->format('M j, Y · g:i A') }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="eye" class="w-3 h-3"></i>
                {{ $announcement->views_count }} view{{ $announcement->views_count === 1 ? '' : 's' }}
            </span>
        </div>

        {{-- Content --}}
        <div class="prose prose-sm dark:prose-invert max-w-none mt-5 text-gray-800 dark:text-slate-200 leading-relaxed">
            {!! nl2br(e($announcement->content)) !!}
        </div>

        {{-- Acknowledgment CTA --}}
        @if($announcement->requires_acknowledgment)
            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-700">
                @if($isAcknowledged)
                    <div class="lmt-alert lmt-alert-success">
                        <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                        <div>
                            <p class="font-bold">Thanks for acknowledging this.</p>
                            <p class="text-xs mt-0.5">You confirmed receipt on {{ $read->acknowledged_at->format('M j, Y · g:i A') }}.</p>
                        </div>
                    </div>
                @else
                    <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-2xl p-4">
                        <div class="flex items-start gap-3">
                            <i data-lucide="check-square" class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"></i>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm text-amber-900 dark:text-amber-100">Action required</p>
                                <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                                    Please confirm that you've read and understood this announcement.
                                </p>
                                <form action="{{ route('employee.announcements.acknowledge', [$tenantSlug, $announcement->id]) }}"
                                      method="POST" class="mt-3"
                                      x-data="{ submitting: false }" @submit="submitting=true">
                                    @csrf
                                    <button type="submit" class="lmt-btn-primary lmt-btn-sm" :disabled="submitting">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        <span x-show="!submitting">I acknowledge</span>
                                        <span x-show="submitting">Saving…</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </article>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection