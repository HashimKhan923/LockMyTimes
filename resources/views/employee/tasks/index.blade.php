@extends('layouts.employee')

@section('title', 'My Tasks')
@section('page-title', 'My Tasks')

@section('content')
<div>
    {{-- ═══════════════════════════════════════════════════════════════
         HERO — task summary
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 grid lg:grid-cols-[1.4fr_1fr] gap-6 items-center">
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">Active workload</p>
                <h1 class="text-white text-2xl lg:text-3xl font-black mt-1" style="font-family:'Plus Jakarta Sans',sans-serif">
                    {{ (int) ($counters->open_count ?? 0) }} open task{{ ($counters->open_count ?? 0) == 1 ? '' : 's' }}
                </h1>
                <p class="text-white/75 text-sm mt-1.5">
                    {{ (int) ($counters->in_progress ?? 0) }} in progress &middot;
                    {{ (int) ($counters->in_review ?? 0) }} in review
                    @if(($counters->overdue ?? 0) > 0)
                        &middot; <span class="text-amber-100">{{ (int) $counters->overdue }} overdue</span>
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('employee.projects.index', $tenantSlug) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-white/15 border border-white/25 text-white hover:bg-white/25 transition-all">
                        <i data-lucide="folder-kanban" class="w-4 h-4"></i> My Projects
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Due today</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $todayDue }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">This week</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ $thisWeekDue }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/20 rounded-2xl p-4">
                    <p class="text-white/65 text-[10px] font-bold uppercase tracking-wider">Done</p>
                    <p class="text-white text-2xl font-black font-mono mt-1">{{ (int) ($counters->done ?? 0) }}</p>
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
         FILTER BAR + SEARCH
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">

        {{-- Top bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-5 border-b border-gray-100 dark:border-slate-700">
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">Tasks</h2>
                <p class="text-xs text-gray-800 mt-0.5">
                    Showing {{ $tasks->total() }} task{{ $tasks->total() == 1 ? '' : 's' }}
                </p>
            </div>

            {{-- Search + project filter (single form) --}}
            <form method="GET" action="{{ route('employee.tasks.index', $tenantSlug) }}"
                  class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="filter"   value="{{ $filter }}">
                <input type="hidden" name="priority" value="{{ $priority }}">

                <div class="relative flex-1 min-w-[180px]">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-800"></i>
                    <input type="search" name="q" value="{{ $search }}"
                           placeholder="Search tasks..."
                           class="lmt-input pl-9" style="min-width:180px;"/>
                </div>

                <select name="project" onchange="this.form.submit()" class="lmt-input" style="width:auto;min-width:150px;">
                    <option value="">All projects</option>
                    @foreach($myProjects as $p)
                        <option value="{{ $p->id }}" {{ (int) $projectId === (int) $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>

                <select name="priority" onchange="this.form.submit()" class="lmt-input" style="width:auto;min-width:120px;">
                    <option value="">All priorities</option>
                    @foreach(['urgent','high','normal','low'] as $p)
                        <option value="{{ $p }}" {{ $priority === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>

                @if($search !== '' || $priority || $projectId)
                    <a href="{{ route('employee.tasks.index', ['tenant' => $tenantSlug, 'filter' => $filter]) }}"
                       class="text-xs font-bold text-gray-800 hover:text-gray-700 px-2">Clear</a>
                @endif
            </form>
        </div>

        {{-- Status chips --}}
        <div class="flex flex-wrap items-center gap-1.5 px-5 pt-4 pb-2 border-b border-gray-100 dark:border-slate-700">
            @php
                $chips = [
                    ['key' => 'open',        'label' => 'Open',        'count' => (int) ($counters->open_count ?? 0)],
                    ['key' => 'todo',        'label' => 'To do',       'count' => (int) ($counters->todo ?? 0)],
                    ['key' => 'in_progress', 'label' => 'In progress', 'count' => (int) ($counters->in_progress ?? 0)],
                    ['key' => 'in_review',   'label' => 'In review',   'count' => (int) ($counters->in_review ?? 0)],
                    ['key' => 'on_hold',     'label' => 'On hold',     'count' => (int) ($counters->on_hold ?? 0)],
                    ['key' => 'done',        'label' => 'Done',        'count' => (int) ($counters->done ?? 0)],
                    ['key' => 'overdue',     'label' => 'Overdue',     'count' => (int) ($counters->overdue ?? 0)],
                    ['key' => 'created',     'label' => 'Created by me','count' => $createdCount],
                    ['key' => 'all',         'label' => 'All',         'count' => (int) ($counters->total ?? 0)],
                ];
            @endphp
            @foreach($chips as $chip)
                @php $active = $filter === $chip['key']; @endphp
                <a href="{{ route('employee.tasks.index', array_filter([
                        'tenant'   => $tenantSlug,
                        'filter'   => $chip['key'],
                        'priority' => $priority,
                        'project'  => $projectId,
                        'q'        => $search ?: null,
                   ])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all
                          {{ $active
                                ? 'border-transparent text-white'
                                : 'border-gray-200 dark:border-slate-700 text-gray-800 hover:border-gray-300' }}"
                   @if($active) style="background:var(--brand-500);" @endif>
                    {{ $chip['label'] }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $active ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-700' }}">
                        {{ $chip['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Tasks list --}}
        @if($tasks->isEmpty())
            <div class="text-center py-16 px-5">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                    <i data-lucide="check-square" class="w-7 h-7 text-gray-800"></i>
                </div>
                <p class="text-sm font-bold text-gray-700 dark:text-slate-200">No tasks found</p>
                <p class="text-xs text-gray-800 mt-1">
                    @if($filter === 'open')
                        You're all caught up. Take a moment to celebrate.
                    @elseif($filter === 'overdue')
                        Nothing overdue — looking good.
                    @else
                        Try a different filter or clear your search.
                    @endif
                </p>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($tasks as $t)
                    @php
                        [$statusLbl, $statusCls, $statusDotColor] = match($t->status) {
                            'backlog'     => ['Backlog',     'lmt-badge-gray',  '#9ca3af'],
                            'todo'        => ['To do',       'lmt-badge-gray',  '#6b7280'],
                            'in_progress' => ['In progress', 'lmt-badge-amber', '#d97706'],
                            'in_review'   => ['In review',   'lmt-badge-amber', '#7c3aed'],
                            'on_hold'     => ['On hold',     'lmt-badge-gray',  '#9ca3af'],
                            'done'        => ['Done',        'lmt-badge-green', '#10b981'],
                            'cancelled'   => ['Cancelled',   'lmt-badge-gray',  '#6b7280'],
                            default       => [ucfirst($t->status), 'lmt-badge-gray', '#6b7280'],
                        };

                        [$priorityCls, $priorityIcon] = match($t->priority) {
                            'urgent' => ['text-red-600 bg-red-50 dark:bg-red-500/15 dark:text-red-300', 'alert-triangle'],
                            'high'   => ['text-amber-600 bg-amber-50 dark:bg-amber-500/15 dark:text-amber-300', 'arrow-up'],
                            'low'    => ['text-gray-800 bg-gray-50 dark:bg-slate-700 dark:text-gray-400', 'arrow-down'],
                            default  => ['text-gray-800 bg-gray-50 dark:bg-slate-700 dark:text-gray-400', 'minus'],
                        };

                        $isOverdue = $t->due_date && $t->due_date->isPast() && ! in_array($t->status, ['done','cancelled']);
                        $isToday   = $t->due_date && $t->due_date->isToday() && ! in_array($t->status, ['done','cancelled']);
                        $checklistTotal = $t->subtasks_count ?? 0;
                        $checklistDone  = $t->completed_subtasks_count ?? 0;
                    @endphp
                    <a href="{{ route('employee.tasks.show', [$tenantSlug, $t->id]) }}"
                       class="block px-5 py-4 hover:bg-gray-50/70 dark:hover:bg-slate-800/50 transition-colors">
                        <div class="flex items-start gap-3">
                            {{-- Status dot --}}
                            <span class="w-2.5 h-2.5 rounded-full mt-2 flex-shrink-0" style="background:{{ $statusDotColor }};"></span>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono text-[10px] font-bold text-gray-800">{{ $t->task_code }}</span>
                                            @if($t->priority !== 'normal')
                                                <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase {{ $priorityCls }}">
                                                    <i data-lucide="{{ $priorityIcon }}" class="w-2.5 h-2.5"></i>
                                                    {{ $t->priority }}
                                                </span>
                                            @endif
                                            @if($t->type !== 'task')
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 uppercase">{{ $t->type }}</span>
                                            @endif
                                        </div>
                                        <p class="font-bold text-sm text-gray-900 dark:text-slate-100 mt-1 truncate">
                                            {{ $t->title }}
                                        </p>
                                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-gray-800 flex-wrap">
                                            @if($t->project)
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="folder" class="w-3 h-3"></i>
                                                    <span style="color: {{ $t->project->color }};">●</span>
                                                    {{ $t->project->name }}
                                                </span>
                                            @endif
                                            @if($t->due_date)
                                                <span class="inline-flex items-center gap-1 {{ $isOverdue ? 'text-red-600 font-bold' : ($isToday ? 'text-amber-600 font-bold' : '') }}">
                                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                                    {{ $isToday ? 'Today' : $t->due_date->format('M j') }}
                                                </span>
                                            @endif
                                            @if($checklistTotal > 0)
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="list-checks" class="w-3 h-3"></i>
                                                    {{ $checklistDone }}/{{ $checklistTotal }}
                                                </span>
                                            @endif
                                            @if($t->comments_count > 0)
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="message-circle" class="w-3 h-3"></i>
                                                    {{ $t->comments_count }}
                                                </span>
                                            @endif
                                            @if($t->attachments_count > 0)
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="paperclip" class="w-3 h-3"></i>
                                                    {{ $t->attachments_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        {{-- Progress badge --}}
                                        @if($t->progress > 0 && $t->status !== 'done')
                                            <div class="hidden sm:flex items-center gap-1.5">
                                                <div class="w-16 h-1.5 rounded-full bg-gray-200 dark:bg-slate-700 overflow-hidden">
                                                    <div class="h-full rounded-full" style="width:{{ $t->progress }}%; background:var(--brand-500);"></div>
                                                </div>
                                                <span class="font-mono text-[10px] font-bold text-gray-800">{{ $t->progress }}%</span>
                                            </div>
                                        @endif
                                        <span class="{{ $statusCls }}">{{ $statusLbl }}</span>
                                    </div>
                                </div>

                                {{-- Assignee avatars (if not just me) --}}
                                @if($t->assignees->count() > 1)
                                    <div class="flex items-center gap-1 mt-2">
                                        @foreach($t->assignees->take(4) as $a)
                                            @if($a->employee)
                                                <img src="{{ $a->employee->avatar_url }}"
                                                     alt="{{ $a->employee->full_name }}"
                                                     title="{{ $a->employee->full_name }}"
                                                     class="w-5 h-5 rounded-full ring-2 ring-white dark:ring-slate-800 object-cover"/>
                                            @endif
                                        @endforeach
                                        @if($t->assignees->count() > 4)
                                            <span class="text-[10px] font-bold text-gray-800 ml-1">+{{ $t->assignees->count() - 4 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700">
                {{ $tasks->links() }}
            </div>
        @endif
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