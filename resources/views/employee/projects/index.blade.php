@extends('layouts.employee')

@section('title', 'My Projects')
@section('page-title', 'My Projects')

@section('content')
<div>
    {{-- ═══════════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
                My Projects
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ (int) ($counters->total ?? 0) }} project{{ ($counters->total ?? 0) == 1 ? '' : 's' }} you're a part of
            </p>
        </div>
        <a href="{{ route('employee.tasks.index', $tenantSlug) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="check-square" class="w-4 h-4"></i>
            My Tasks
        </a>
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
         FILTER BAR
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-5">

        {{-- Status chips --}}
        <div class="flex flex-wrap items-center gap-1.5">
            @php
                $chips = [
                    ['key' => 'active',    'label' => 'Active',    'count' => (int) ($counters->active ?? 0)],
                    ['key' => 'planning',  'label' => 'Planning',  'count' => (int) ($counters->planning ?? 0)],
                    ['key' => 'on_hold',   'label' => 'On hold',   'count' => (int) ($counters->on_hold ?? 0)],
                    ['key' => 'completed', 'label' => 'Completed', 'count' => (int) ($counters->completed ?? 0)],
                    ['key' => 'all',       'label' => 'All',       'count' => (int) ($counters->total ?? 0)],
                ];
            @endphp
            @foreach($chips as $chip)
                @php $active = $status === $chip['key']; @endphp
                <a href="{{ route('employee.projects.index', array_filter([
                        'tenant' => $tenantSlug,
                        'status' => $chip['key'],
                        'q'      => $search ?: null,
                   ])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all
                          {{ $active
                                ? 'border-transparent text-white'
                                : 'border-gray-200 dark:border-slate-700 text-gray-500 hover:border-gray-300' }}"
                   @if($active) style="background:var(--brand-500);" @endif>
                    {{ $chip['label'] }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $active ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">
                        {{ $chip['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('employee.projects.index', $tenantSlug) }}" class="relative">
            <input type="hidden" name="status" value="{{ $status }}">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="search" name="q" value="{{ $search }}"
                   placeholder="Search projects..."
                   class="lmt-input pl-9" style="min-width:220px;"/>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PROJECT GRID
    ═══════════════════════════════════════════════════════════════ --}}
    @if($projects->isEmpty())
        <div class="lmt-card text-center py-16 px-5" data-lmt-anim="fade-up">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                <i data-lucide="folder-kanban" class="w-7 h-7 text-gray-300"></i>
            </div>
            <p class="text-sm font-bold text-gray-700 dark:text-slate-200">No projects found</p>
            <p class="text-xs text-gray-500 mt-1">
                @if($search !== '')
                    No projects match "{{ $search }}". Try a different search.
                @else
                    You're not part of any projects yet. Your manager can add you to one.
                @endif
            </p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($projects as $p)
                @php
                    [$pStatusLbl, $pStatusCls] = match($p->status) {
                        'planning'  => ['Planning',  'lmt-badge-amber'],
                        'active'    => ['Active',    'lmt-badge-green'],
                        'on_hold'   => ['On hold',   'lmt-badge-gray'],
                        'completed' => ['Completed', 'lmt-badge-gray'],
                        'cancelled' => ['Cancelled', 'lmt-badge-gray'],
                        default     => [ucfirst($p->status), 'lmt-badge-gray'],
                    };
                    $isOverdue = $p->due_date && $p->due_date->isPast() && ! in_array($p->status, ['completed','cancelled']);
                @endphp
                <a href="{{ route('employee.projects.board', [$tenantSlug, $p->id]) }}"
                   class="lmt-card p-5 hover:shadow-md transition-all relative overflow-hidden block group"
                   data-lmt-anim="fade-up">

                    {{-- Color accent bar --}}
                    <div class="absolute top-0 left-0 right-0 h-1" style="background:{{ $p->color }};"></div>

                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-mono text-[10px] font-bold text-gray-400">{{ $p->code }}</p>
                            <h3 class="font-black text-base text-gray-900 dark:text-slate-100 mt-0.5 truncate group-hover:text-brand-600 transition-colors">
                                {{ $p->name }}
                            </h3>
                        </div>
                        <span class="{{ $pStatusCls }} flex-shrink-0">{{ $pStatusLbl }}</span>
                    </div>

                    @if($p->description)
                        <p class="text-xs text-gray-500 dark:text-slate-400 line-clamp-2 mb-3">{{ $p->description }}</p>
                    @endif

                    {{-- Progress bar (project-wide) --}}
                    @if($p->progress > 0)
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Project progress</span>
                                <span class="font-mono text-[10px] font-bold text-gray-700 dark:text-slate-300">{{ $p->progress }}%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ $p->progress }}%; background:{{ $p->color }};"></div>
                            </div>
                        </div>
                    @endif

                    {{-- My tasks stats --}}
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="bg-gray-50/70 dark:bg-slate-800/60 rounded-lg p-2">
                            <p class="text-[9px] text-gray-400 font-bold uppercase">My tasks</p>
                            <p class="font-mono font-black text-base text-gray-900 dark:text-slate-100">{{ $p->_my_total ?? 0 }}</p>
                        </div>
                        <div class="bg-gray-50/70 dark:bg-slate-800/60 rounded-lg p-2">
                            <p class="text-[9px] text-gray-400 font-bold uppercase">Open</p>
                            <p class="font-mono font-black text-base" style="color:var(--brand-600);">{{ $p->_my_open ?? 0 }}</p>
                        </div>
                        <div class="bg-gray-50/70 dark:bg-slate-800/60 rounded-lg p-2">
                            <p class="text-[9px] text-gray-400 font-bold uppercase">Overdue</p>
                            <p class="font-mono font-black text-base {{ ($p->_my_overdue ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $p->_my_overdue ?? 0 }}</p>
                        </div>
                    </div>

                    {{-- Footer meta --}}
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-slate-700 text-[10px] text-gray-500">
                        <div class="flex items-center gap-2">
                            @if($p->_my_role)
                                <span class="font-bold uppercase">
                                    {{ str_replace('_', ' ', $p->_my_role) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            @if($p->due_date)
                                <span class="inline-flex items-center gap-1 {{ $isOverdue ? 'text-red-600 font-bold' : '' }}">
                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                    {{ $p->due_date->format('M j') }}
                                </span>
                            @endif
                            @if($p->manager)
                                <img src="{{ $p->manager->avatar_url }}"
                                     alt="{{ $p->manager->full_name }}"
                                     title="Manager: {{ $p->manager->full_name }}"
                                     class="w-5 h-5 rounded-full ring-2 ring-white dark:ring-slate-800 object-cover"/>
                            @endif
                            <span class="inline-flex items-center gap-1 font-bold text-brand-600 group-hover:underline">
                                <i data-lucide="kanban-square" class="w-3 h-3"></i>
                                Board
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $projects->links() }}
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