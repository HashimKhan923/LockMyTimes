@extends('layouts.employee')

@section('title', 'Announcements')
@section('page-title', 'Announcements')

@section('content')
<div>
    {{-- ═══════════════════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 grid lg:grid-cols-[1.4fr_1fr] gap-6 items-center">
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Company news</p>
                <h1 class="text-white text-2xl lg:text-3xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                    @if($counters->unread > 0)
                        {{ $counters->unread }} new {{ $counters->unread === 1 ? 'announcement' : 'announcements' }}
                    @elseif($counters->needs_ack > 0)
                        {{ $counters->needs_ack }} {{ $counters->needs_ack === 1 ? 'item needs' : 'items need' }} acknowledgment
                    @else
                        You're all caught up
                    @endif
                </h1>
                <p class="text-white/75 text-sm mt-1.5">
                    {{ $counters->total }} total &middot;
                    {{ $counters->active_polls }} active {{ $counters->active_polls === 1 ? 'poll' : 'polls' }}
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Unread</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $counters->unread }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Need ack.</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $counters->needs_ack }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Polls</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $counters->active_polls }}</p>
                </div>
            </div>
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

    {{-- ═══════════════════════════════════════════════════════════════
         FILTER TABS
    ═══════════════════════════════════════════════════════════════ --}}
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
                            : 'border-gray-200 dark:border-slate-700 text-gray-500 hover:border-gray-300' }}"
               @if($active) style="background:var(--brand-500);" @endif>
                <i data-lucide="{{ $chip['icon'] }}" class="w-3.5 h-3.5"></i>
                {{ $chip['label'] }}
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $active ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">
                    {{ $chip['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-[2fr_1fr] gap-5">

        {{-- ═════ LEFT: announcement list ═════ --}}
        <div class="space-y-4">
            @if($announcements->isEmpty())
                <div class="lmt-card text-center py-16 px-5" data-lmt-anim="fade-up">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                        <i data-lucide="megaphone" class="w-7 h-7 text-gray-300"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-700 dark:text-slate-200">
                        @if($filter === 'unread') Nothing unread — nice
                        @elseif($filter === 'acknowledgments') No pending acknowledgments
                        @else No announcements yet
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($filter !== 'all')
                            <a href="{{ route('employee.announcements.index', $tenantSlug) }}" class="hover:underline" style="color:var(--brand-500);">View all announcements</a>
                        @else
                            Check back later for company news.
                        @endif
                    </p>
                </div>
            @else
                @foreach($announcements as $a)
                    @php
                        [$priLbl, $priCls, $priIcon] = match($a->priority) {
                            'urgent' => ['Urgent', 'text-red-600 bg-red-50 dark:bg-red-500/15 dark:text-red-300', 'alert-triangle'],
                            'high'   => ['High',   'text-amber-600 bg-amber-50 dark:bg-amber-500/15 dark:text-amber-300', 'arrow-up'],
                            'low'    => ['Low',    'text-gray-400 bg-gray-50 dark:bg-slate-700', 'arrow-down'],
                            default  => ['',       '', ''],
                        };
                    @endphp
                    <a href="{{ route('employee.announcements.show', [$tenantSlug, $a->id]) }}"
                       class="lmt-card hover:shadow-md transition-all block group {{ ! $a->_is_read ? 'ring-2' : '' }}"
                       @if(! $a->_is_read) style="--tw-ring-color: var(--brand-500);" @endif
                       data-lmt-anim="fade-up">

                        @if($a->banner_image)
                            <div class="-m-5 mb-4 h-32 rounded-t-2xl overflow-hidden bg-gray-100 dark:bg-slate-800">
                                <img src="{{ asset('storage/' . $a->banner_image) }}" alt=""
                                     class="w-full h-full object-cover"/>
                            </div>
                        @endif

                        <div class="flex items-start gap-3">
                            {{-- Unread dot --}}
                            @if(! $a->_is_read)
                                <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background:var(--brand-500);" title="Unread"></span>
                            @else
                                <span class="w-2 h-2 mt-2 flex-shrink-0"></span>
                            @endif

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    @if($a->priority !== 'normal')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase {{ $priCls }}">
                                            <i data-lucide="{{ $priIcon }}" class="w-2.5 h-2.5"></i>
                                            {{ $priLbl }}
                                        </span>
                                    @endif
                                    @if($a->requires_acknowledgment)
                                        @if($a->_is_acknowledged)
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                                <i data-lucide="check-circle" class="w-2.5 h-2.5"></i>
                                                Acknowledged
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                                <i data-lucide="check-square" class="w-2.5 h-2.5"></i>
                                                Action needed
                                            </span>
                                        @endif
                                    @endif
                                    @if($a->expires_at && $a->expires_at->lte(now()->addDays(3)))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300">
                                            <i data-lucide="clock" class="w-2.5 h-2.5"></i>
                                            Expires {{ $a->expires_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>

                                <h3 class="font-black text-base lg:text-lg text-gray-900 dark:text-slate-100 group-hover:underline" style="font-family:'Plus Jakarta Sans',sans-serif">
                                    {{ $a->title }}
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-slate-400 mt-1 line-clamp-2">
                                    {{ Str::limit(strip_tags($a->content), 180) }}
                                </p>

                                <div class="flex items-center gap-3 mt-3 text-[11px] text-gray-500 flex-wrap">
                                    @if($a->creator)
                                        <span class="inline-flex items-center gap-1.5">
                                            <i data-lucide="user" class="w-3 h-3"></i>
                                            {{ $a->creator->name }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3 h-3"></i>
                                        {{ ($a->publish_at ?? $a->created_at)->diffForHumans() }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="eye" class="w-3 h-3"></i>
                                        {{ $a->views_count }} view{{ $a->views_count === 1 ? '' : 's' }}
                                    </span>
                                </div>
                            </div>

                            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 mt-1 flex-shrink-0"></i>
                        </div>
                    </a>
                @endforeach

                <div class="mt-2">
                    {{ $announcements->links() }}
                </div>
            @endif
        </div>

        {{-- ═════ RIGHT: active polls sidebar ═════ --}}
        <div class="space-y-4">
            <div class="lmt-card" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-black text-gray-900 dark:text-slate-100 flex items-center gap-1.5">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-gray-400"></i>
                        Active polls
                    </h3>
                    @if($activePolls->count() > 0)
                        <a href="{{ route('employee.announcements.index', ['tenant' => $tenantSlug, 'filter' => 'polls']) }}"
                           class="text-[10px] font-bold hover:underline" style="color:var(--brand-500);">View all</a>
                    @endif
                </div>

                @if($activePolls->isEmpty())
                    <div class="text-center py-8">
                        <i data-lucide="bar-chart-2" class="w-6 h-6 text-gray-300 mx-auto mb-2"></i>
                        <p class="text-xs text-gray-500">No active polls right now.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($activePolls as $p)
                            <a href="{{ route('employee.announcements.polls.show', [$tenantSlug, $p->id]) }}"
                               class="block p-3 rounded-xl border border-gray-100 dark:border-slate-700 hover:border-gray-200 dark:hover:border-slate-600 transition-colors">
                                <div class="flex items-center gap-2 mb-1">
                                    @if($p->_has_voted)
                                        <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 uppercase">
                                            <i data-lucide="check" class="w-2.5 h-2.5"></i> Voted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase" style="background:var(--brand-50);color:var(--brand-600);">
                                            <i data-lucide="circle" class="w-2.5 h-2.5"></i> Open
                                        </span>
                                    @endif
                                    @if($p->ends_at)
                                        <span class="text-[10px] text-gray-400">Ends {{ $p->ends_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                                <p class="font-bold text-sm text-gray-900 dark:text-slate-100 line-clamp-2">{{ $p->question }}</p>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    {{ count($p->options ?? []) }} option{{ count($p->options ?? []) === 1 ? '' : 's' }}
                                    @if($p->is_anonymous) &middot; Anonymous @endif
                                </p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
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