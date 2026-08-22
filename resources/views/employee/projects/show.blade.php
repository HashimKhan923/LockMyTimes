@extends('layouts.employee')

@section('title', $project->name)
@section('page-title', 'Project Detail')

@section('content')
<div class="max-w-6xl mx-auto">

    @php
        [$statusLbl, $statusColor, $statusBg, $statusIcon] = match($project->status) {
            'planning'  => ['Planning',  '#92400e', '#fffbeb', 'pencil'],
            'active'    => ['Active',    '#065f46', '#ecfdf5', 'activity'],
            'on_hold'   => ['On hold',   '#6b7280', '#f3f4f6', 'pause-circle'],
            'completed' => ['Completed', '#065f46', '#ecfdf5', 'check-circle'],
            'cancelled' => ['Cancelled', '#6b7280', '#f3f4f6', 'x-circle'],
            'archived'  => ['Archived',  '#6b7280', '#f3f4f6', 'archive'],
            default     => [ucfirst($project->status), '#6b7280', '#f3f4f6', 'circle'],
        };

        $isOverdue = $project->due_date && $project->due_date->isPast() && ! in_array($project->status, ['completed','cancelled','archived']);
    @endphp

    {{-- Back nav + board toggle --}}
    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('employee.projects.index', $tenantSlug) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 dark:hover:text-slate-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>All Projects</span>
        </a>
        <a href="{{ route('employee.projects.board', [$tenantSlug, $project->id]) }}"
           class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            Kanban Board
        </a>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         HEADER CARD
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-6 mb-5 relative overflow-hidden"
         style="background:linear-gradient(135deg, {{ $project->color }} 0%, {{ $project->color }}dd 100%);"
         data-lmt-anim="fade-up">

        <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-2 flex-wrap mb-2">
                <span class="font-mono text-xs font-bold text-white/70">{{ $project->code }}</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/20 backdrop-blur text-white">
                    <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                    {{ $statusLbl }}
                </span>
                @if($myMember)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/20 backdrop-blur text-white uppercase">
                        Your role: {{ str_replace('_', ' ', $myMember->role) }}
                    </span>
                @endif
            </div>
            <h1 class="text-2xl lg:text-3xl font-black text-white" style="font-family:'Plus Jakarta Sans',sans-serif">
                {{ $project->name }}
            </h1>
            @if($project->description)
                <p class="text-white/85 text-sm mt-2 max-w-3xl">{{ $project->description }}</p>
            @endif

            <div class="flex items-center gap-4 mt-4 text-xs text-white/80 flex-wrap">
                @if($project->manager)
                    <span class="inline-flex items-center gap-1.5">
                        <img src="{{ $project->manager->avatar_url }}" alt="{{ $project->manager->full_name }}"
                             class="w-5 h-5 rounded-full ring-2 ring-white/30 object-cover"/>
                        <span>Managed by <span class="font-bold text-white">{{ $project->manager->full_name }}</span></span>
                    </span>
                @endif
                @if($project->start_date)
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="play" class="w-3 h-3"></i>
                        Started {{ $project->start_date->format('M j, Y') }}
                    </span>
                @endif
                @if($project->due_date)
                    <span class="inline-flex items-center gap-1.5 {{ $isOverdue ? 'text-amber-200 font-bold' : '' }}">
                        <i data-lucide="calendar" class="w-3 h-3"></i>
                        Due {{ $project->due_date->format('M j, Y') }}
                    </span>
                @endif
            </div>

            {{-- Progress bar --}}
            <div class="mt-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-bold text-white/65 uppercase tracking-wider">Project progress</span>
                    <span class="font-mono text-sm font-black text-white">{{ $project->progress }}%</span>
                </div>
                <div class="h-2 rounded-full bg-white/15 overflow-hidden backdrop-blur">
                    <div class="h-full rounded-full bg-white transition-all" style="width:{{ $project->progress }}%;"></div>
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

    {{-- ═══════════════════════════════════════════════════════════════
         STATS ROW — my contribution
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @php
            $stats = [
                ['My tasks',  (int) ($myStats->total ?? 0),       'check-square', 'gray'],
                ['Open',      (int) (($myStats->total ?? 0) - ($myStats->done ?? 0)), 'circle', 'brand'],
                ['In review', (int) ($myStats->in_review ?? 0),   'eye', 'amber'],
                ['Overdue',   (int) ($myStats->overdue ?? 0),     'alert-triangle', ($myStats->overdue ?? 0) > 0 ? 'red' : 'gray'],
            ];
            $colorClasses = [
                'gray'  => ['bg' => 'bg-gray-100 dark:bg-slate-700', 'fg' => 'text-gray-800'],
                'amber' => ['bg' => 'bg-amber-50 dark:bg-amber-500/15', 'fg' => 'text-amber-600'],
                'red'   => ['bg' => 'bg-red-50 dark:bg-red-500/15', 'fg' => 'text-red-600'],
                'brand' => ['bg' => '', 'fg' => '', 'inline' => 'background:var(--brand-50);color:var(--brand-600);'],
            ];
        @endphp
        @foreach($stats as [$label, $value, $icon, $color])
            @php $c = $colorClasses[$color]; @endphp
            <div class="lmt-card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] text-gray-800 font-bold uppercase tracking-wider">{{ $label }}</p>
                        <p class="text-2xl font-black font-mono mt-1 text-gray-900 dark:text-slate-100">{{ $value }}</p>
                    </div>
                    <div class="w-9 h-9 rounded-xl {{ $c['bg'] ?? '' }} {{ $c['fg'] ?? '' }} flex items-center justify-center"
                         @if(! empty($c['inline'])) style="{{ $c['inline'] }}" @endif>
                        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-[2fr_1fr] gap-5">

        {{-- ═════ LEFT: my tasks in this project ═════ --}}
        <div class="space-y-5">
            <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">
                <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-slate-700">
                    <div>
                        <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">My tasks in this project</h2>
                        <p class="text-xs text-gray-800 mt-0.5">{{ $myTasks->count() }} task{{ $myTasks->count() == 1 ? '' : 's' }} assigned to you</p>
                    </div>
                </div>

                @if($myTasks->isEmpty())
                    <div class="text-center py-16 px-5">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                            <i data-lucide="check-square" class="w-7 h-7 text-gray-800"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-700 dark:text-slate-200">No tasks assigned</p>
                        <p class="text-xs text-gray-800 mt-1">You're a member of this project but have no tasks assigned yet.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-slate-700">
                        @foreach($myTasks as $t)
                            @php
                                [$statusLblT, $statusClsT, $statusDot] = match($t->status) {
                                    'backlog'     => ['Backlog',     'lmt-badge-gray',  '#9ca3af'],
                                    'todo'        => ['To do',       'lmt-badge-gray',  '#6b7280'],
                                    'in_progress' => ['In progress', 'lmt-badge-amber', '#d97706'],
                                    'in_review'   => ['In review',   'lmt-badge-amber', '#7c3aed'],
                                    'on_hold'     => ['On hold',     'lmt-badge-gray',  '#9ca3af'],
                                    'done'        => ['Done',        'lmt-badge-green', '#10b981'],
                                    'cancelled'   => ['Cancelled',   'lmt-badge-gray',  '#6b7280'],
                                    default       => [ucfirst($t->status), 'lmt-badge-gray', '#6b7280'],
                                };
                                $tIsOverdue = $t->due_date && $t->due_date->isPast() && ! in_array($t->status, ['done','cancelled']);
                            @endphp
                            <a href="{{ route('employee.tasks.show', [$tenantSlug, $t->id]) }}"
                               class="block px-5 py-4 hover:bg-gray-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <div class="flex items-start gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0" style="background:{{ $statusDot }};"></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono text-[10px] font-bold text-gray-800">{{ $t->task_code }}</span>
                                            @if($t->priority !== 'normal')
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase
                                                    {{ $t->priority === 'urgent' ? 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300' : '' }}
                                                    {{ $t->priority === 'high' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' : '' }}
                                                    {{ $t->priority === 'low' ? 'bg-gray-50 text-gray-800 dark:bg-slate-700' : '' }}">
                                                    {{ $t->priority }}
                                                </span>
                                            @endif
                                            @if($t->taskList)
                                                <span class="text-[10px] text-gray-800">{{ $t->taskList->name }}</span>
                                            @endif
                                        </div>
                                        <p class="font-bold text-sm text-gray-900 dark:text-slate-100 mt-1 truncate">{{ $t->title }}</p>
                                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-gray-800 flex-wrap">
                                            @if($t->due_date)
                                                <span class="inline-flex items-center gap-1 {{ $tIsOverdue ? 'text-red-600 font-bold' : '' }}">
                                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                                    {{ $t->due_date->format('M j') }}
                                                </span>
                                            @endif
                                            @if(($t->subtasks_count ?? 0) > 0)
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="list-checks" class="w-3 h-3"></i>
                                                    {{ $t->completed_subtasks_count }}/{{ $t->subtasks_count }}
                                                </span>
                                            @endif
                                            @if($t->progress > 0 && $t->status !== 'done')
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="trending-up" class="w-3 h-3"></i>
                                                    {{ $t->progress }}%
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="{{ $statusClsT }} flex-shrink-0">{{ $statusLblT }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ═════ RIGHT: project info + team ═════ --}}
        <div class="space-y-5">

            {{-- Project meta --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i> Project info
                </h3>
                <div class="space-y-3">
                    @php
                        $rows = [
                            ['Type',           ucfirst($project->type), 'tag'],
                            ['Tasks total',    (int) ($projectStats->total ?? 0) . ' (' . (int) ($projectStats->completed ?? 0) . ' done)', 'check-square'],
                        ];
                        if (($projectStats->overdue ?? 0) > 0) {
                            $rows[] = ['Overdue tasks', (int) $projectStats->overdue, 'alert-triangle'];
                        }
                        if ($project->department) {
                            $rows[] = ['Department', $project->department->name ?? '—', 'building'];
                        }
                        if ($project->start_date) {
                            $rows[] = ['Start date', $project->start_date->format('M j, Y'), 'play'];
                        }
                    @endphp
                    @foreach($rows as [$label, $value, $icon])
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800 last:border-b-0">
                            <div class="flex items-center gap-2 text-gray-800 dark:text-slate-400 text-xs">
                                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                                <span>{{ $label }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 text-right break-words">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Team --}}
            @if($members->isNotEmpty())
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i> Team
                        <span class="font-mono text-[10px] text-gray-800 ml-1">({{ $members->count() }})</span>
                    </h3>
                    <div class="space-y-2">
                        @foreach($members->take(10) as $m)
                            @if($m->employee)
                                <div class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <img src="{{ $m->employee->avatar_url }}" alt="{{ $m->employee->full_name }}"
                                         class="w-8 h-8 rounded-full ring-2 ring-white dark:ring-slate-700 object-cover"/>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate">
                                            {{ $m->employee->full_name }}
                                            @if($m->employee_id === $emp->id)
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded ml-1" style="background:var(--brand-50);color:var(--brand-600);">YOU</span>
                                            @endif
                                        </p>
                                        <p class="text-[10px] text-gray-800 font-bold uppercase">{{ str_replace('_', ' ', $m->role) }}</p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        @if($members->count() > 10)
                            <p class="text-[10px] text-gray-800 text-center mt-2">+ {{ $members->count() - 10 }} more</p>
                        @endif
                    </div>
                </div>
            @endif
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