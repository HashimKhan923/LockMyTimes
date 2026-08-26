@extends('layouts.employee')

@section('title', 'Polls')
@section('page-title', 'Polls')

@section('content')
<div>
    {{-- Hero --}}
    <div class="rounded-2xl p-5 lg:p-6 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Polls</p>
                <h1 class="text-white text-2xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ $counters->active_polls }} active {{ $counters->active_polls === 1 ? 'poll' : 'polls' }}
                </h1>
                <p class="text-white/75 text-sm mt-1.5">Share your input on company-wide questions</p>
            </div>
            <a href="{{ route('employee.announcements.index', $tenantSlug) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold bg-white/15 border border-white/25 text-white hover:bg-white/25 transition-all">
                <i data-lucide="megaphone" class="w-4 h-4"></i>
                Announcements
            </a>
        </div>
    </div>

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

    {{-- Filter tabs (same as announcements page) --}}
    <div class="flex flex-wrap items-center gap-1.5 mb-5">
        @php
            $chips = [
                ['key' => 'all',             'label' => 'All',           'count' => $counters->total,        'icon' => 'list'],
                ['key' => 'unread',          'label' => 'Unread',        'count' => $counters->unread,       'icon' => 'circle'],
                ['key' => 'acknowledgments', 'label' => 'Need response', 'count' => $counters->needs_ack,    'icon' => 'check-square'],
                ['key' => 'polls',           'label' => 'Polls',         'count' => $counters->active_polls, 'icon' => 'bar-chart-3'],
            ];
        @endphp
        @foreach($chips as $chip)
            @php $active = $filter === $chip['key']; @endphp
            <a href="{{ route('employee.announcements.index', ['tenant' => $tenantSlug, 'filter' => $chip['key']]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all
                      {{ $active
                            ? 'border-transparent text-white'
                            : 'border-gray-200 dark:border-slate-700 text-gray-800 hover:border-gray-300' }}"
               @if($active) style="background:var(--brand-500);" @endif>
                <i data-lucide="{{ $chip['icon'] }}" class="w-3.5 h-3.5"></i>
                {{ $chip['label'] }}
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $active ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">
                    {{ $chip['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- Polls grid --}}
    @if($polls->isEmpty())
        <div class="lmt-card text-center py-16 px-5" data-lmt-anim="fade-up">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                <i data-lucide="bar-chart-2" class="w-7 h-7 text-gray-800"></i>
            </div>
            <p class="text-sm font-bold text-gray-800 dark:text-slate-200">No polls yet</p>
            <p class="text-xs text-gray-800 mt-1">Polls will show up here when they're posted.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($polls as $p)
                @php
                    [$statusLbl, $statusCls] = match(true) {
                        $p->_is_active && $p->_has_voted   => ['Voted',  'lmt-badge-green'],
                        $p->_is_active                      => ['Open',   'lmt-badge-amber'],
                        $p->status === 'closed'             => ['Closed', 'lmt-badge-gray'],
                        default                             => [ucfirst($p->status), 'lmt-badge-gray'],
                    };
                    $typeLabel = match($p->type) {
                        'single_choice'   => 'Single choice',
                        'multiple_choice' => 'Multiple choice',
                        'rating'          => 'Rating',
                        default           => ucfirst(str_replace('_',' ', $p->type)),
                    };
                @endphp
                <a href="{{ route('employee.announcements.polls.show', [$tenantSlug, $p->id]) }}"
                   class="lmt-card p-5 hover:shadow-md transition-all block group"
                   data-lmt-anim="fade-up">

                    <div class="flex items-center gap-2 flex-wrap mb-2">
                        <span class="{{ $statusCls }}">{{ $statusLbl }}</span>
                        <span class="text-[9px] font-bold text-gray-800 uppercase tracking-wider">{{ $typeLabel }}</span>
                        @if($p->is_anonymous)
                            <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                                <i data-lucide="eye-off" class="w-2.5 h-2.5"></i>
                                Anonymous
                            </span>
                        @endif
                    </div>

                    <h3 class="font-black text-sm text-gray-900 dark:text-slate-100 group-hover:underline line-clamp-2">{{ $p->question }}</h3>

                    @if($p->description)
                        <p class="text-xs text-gray-800 dark:text-slate-400 line-clamp-2 mt-1.5">{{ $p->description }}</p>
                    @endif

                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between text-[11px] text-gray-800">
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="list" class="w-3 h-3"></i>
                            {{ count($p->options ?? []) }} option{{ count($p->options ?? []) === 1 ? '' : 's' }}
                        </span>
                        @if($p->ends_at)
                            <span class="inline-flex items-center gap-1.5 {{ ! $p->_is_active ? 'text-gray-800' : '' }}">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                @if($p->_is_active)
                                    Ends {{ $p->ends_at->diffForHumans() }}
                                @else
                                    Ended {{ $p->ends_at->diffForHumans() }}
                                @endif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                {{ $p->created_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $polls->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection