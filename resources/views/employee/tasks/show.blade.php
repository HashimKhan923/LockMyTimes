@extends('layouts.employee')

@section('title', 'Task ' . $task->task_code)
@section('page-title', 'Task Detail')

@section('content')
<div class="max-w-6xl mx-auto" x-data="taskDetail()">

    @php
        [$statusLbl, $statusColor, $statusBg, $statusIcon] = match($task->status) {
            'backlog'     => ['Backlog',     '#6b7280', '#f3f4f6', 'inbox'],
            'todo'        => ['To do',       '#6b7280', '#f3f4f6', 'circle-dashed'],
            'in_progress' => ['In progress', '#d97706', '#fffbeb', 'loader-circle'],
            'in_review'   => ['In review',   '#7c3aed', '#f5f3ff', 'eye'],
            'on_hold'     => ['On hold',     '#6b7280', '#f3f4f6', 'pause-circle'],
            'done'        => ['Done',        '#065f46', '#ecfdf5', 'check-circle'],
            'cancelled'   => ['Cancelled',   '#6b7280', '#f3f4f6', 'x-circle'],
            default       => [ucfirst($task->status), '#6b7280', '#f3f4f6', 'circle'],
        };

        [$priorityCls, $priorityIcon] = match($task->priority) {
            'urgent' => ['text-red-600 bg-red-50 dark:bg-red-500/15 dark:text-red-300', 'alert-triangle'],
            'high'   => ['text-amber-600 bg-amber-50 dark:bg-amber-500/15 dark:text-amber-300', 'arrow-up'],
            'low'    => ['text-gray-400 bg-gray-50 dark:bg-slate-700', 'arrow-down'],
            default  => ['text-gray-500 bg-gray-50 dark:bg-slate-700', 'minus'],
        };

        $isOverdue = $task->due_date && $task->due_date->isPast() && ! in_array($task->status, ['done','cancelled']);

        // Available status options for employees
        $statusOptions = [
            'todo'        => ['To do',       'circle-dashed'],
            'in_progress' => ['In progress', 'loader-circle'],
            'in_review'   => ['In review',   'eye'],
            'on_hold'     => ['On hold',     'pause-circle'],
            'done'        => ['Done',        'check-circle'],
        ];
    @endphp

    {{-- Top nav --}}
    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('employee.tasks.index', $tenantSlug) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-slate-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>All Tasks</span>
        </a>

        @if($task->project)
            <a href="{{ route('employee.projects.show', [$tenantSlug, $task->project_id]) }}"
               class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-gray-700 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-slate-700 hover:border-gray-300 transition-all">
                <span class="w-2 h-2 rounded-full" style="background:{{ $task->project->color }};"></span>
                {{ $task->project->name }}
                <i data-lucide="external-link" class="w-3 h-3"></i>
            </a>
        @endif
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
         HEADER CARD
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card mb-5" data-lmt-anim="fade-up">
        <div class="flex items-center gap-2 flex-wrap mb-2">
            <span class="font-mono text-xs font-bold text-gray-400">{{ $task->task_code }}</span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                  style="background:{{ $statusBg }};color:{{ $statusColor }};">
                <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                {{ $statusLbl }}
            </span>
            @if($task->priority !== 'normal')
                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-2 py-0.5 rounded uppercase {{ $priorityCls }}">
                    <i data-lucide="{{ $priorityIcon }}" class="w-3 h-3"></i>
                    {{ $task->priority }}
                </span>
            @endif
            @if($task->type !== 'task')
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 uppercase">{{ $task->type }}</span>
            @endif
            @if($isOverdue)
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300 uppercase">Overdue</span>
            @endif
        </div>
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-slate-100"
            style="font-family:'Plus Jakarta Sans',sans-serif">
            {{ $task->title }}
        </h1>
        @if($task->description)
            <div class="mt-3 text-sm text-gray-600 dark:text-slate-300 whitespace-pre-line leading-relaxed">{!! nl2br(e($task->description)) !!}</div>
        @endif
    </div>

    <div class="grid lg:grid-cols-[1.4fr_1fr] gap-5">

        {{-- ═════ LEFT: status, progress, checklist, comments ═════ --}}
        <div class="space-y-5">

            {{-- Status changer + progress slider --}}
            @if($canEdit)
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                        <i data-lucide="settings" class="w-3.5 h-3.5"></i> Update task
                    </h3>

                    {{-- Status grid --}}
                    <p class="text-xs font-bold text-gray-700 dark:text-slate-300 mb-2">Status</p>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 mb-5">
                        @foreach($statusOptions as $key => [$label, $icon])
                            <form action="{{ route('employee.tasks.status', [$tenantSlug, $task->id]) }}" method="POST"
                                  x-data="{ submitting: false }" @submit="submitting=true">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $key }}">
                                <button type="submit"
                                        :disabled="submitting"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-bold border-2 transition-all
                                               {{ $task->status === $key
                                                    ? 'border-transparent text-white'
                                                    : 'border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:border-gray-300' }}"
                                        @if($task->status === $key) style="background:var(--brand-500);" @endif>
                                    <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                                    {{ $label }}
                                </button>
                            </form>
                        @endforeach
                    </div>

                    {{-- Progress slider --}}
                    <p class="text-xs font-bold text-gray-700 dark:text-slate-300 mb-2">
                        Progress
                        <span class="font-mono text-base ml-1" style="color:var(--brand-600);" x-text="progress + '%'"></span>
                    </p>
                    <form action="{{ route('employee.tasks.progress', [$tenantSlug, $task->id]) }}" method="POST"
                          x-data="{ submitting: false }" @submit="submitting=true">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-center gap-3">
                            <input type="range" name="progress" min="0" max="100" step="5"
                                   x-model.number="progress"
                                   class="flex-1"
                                   style="accent-color:var(--brand-500);"/>
                            <button type="submit"
                                    class="lmt-btn-primary lmt-btn-sm flex-shrink-0"
                                    :disabled="submitting || progress === {{ (int) $task->progress }}">
                                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                Save
                            </button>
                        </div>
                        <div class="grid grid-cols-5 gap-1 mt-2">
                            @foreach([0, 25, 50, 75, 100] as $pct)
                                <button type="button" @click="progress={{ $pct }}"
                                        class="text-[10px] font-bold text-gray-400 hover:text-gray-700 dark:hover:text-slate-300 py-1">
                                    {{ $pct }}%
                                </button>
                            @endforeach
                        </div>
                    </form>
                </div>
            @endif

            {{-- Checklist --}}
            @if($task->checklists->isNotEmpty())
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="list-checks" class="w-3.5 h-3.5"></i> Checklist
                        </h3>
                        <span class="text-xs font-bold text-gray-500">
                            {{ $checklistDone }}/{{ $checklistTotal }}
                            <span class="font-mono ml-1" style="color:var(--brand-600);">({{ $checklistPct }}%)</span>
                        </span>
                    </div>

                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden mb-4">
                        <div class="h-full rounded-full transition-all"
                             style="width:{{ $checklistPct }}%; background:linear-gradient(90deg,var(--brand-500),var(--brand-600));"></div>
                    </div>

                    <div class="space-y-1.5">
                        @foreach($task->checklists as $item)
                            <div class="flex items-start gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/60 transition-colors group">
                                @if($canEdit)
                                    <form action="{{ route('employee.tasks.checklist.toggle', [$tenantSlug, $task->id, $item->id]) }}"
                                          method="POST" class="inline mt-0.5">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="w-4 h-4 rounded border-2 flex items-center justify-center transition-all flex-shrink-0
                                                       {{ $item->is_completed
                                                            ? 'border-transparent'
                                                            : 'border-gray-300 dark:border-slate-600 hover:border-gray-400' }}"
                                                @if($item->is_completed) style="background:var(--brand-500);" @endif>
                                            @if($item->is_completed)
                                                <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                            @endif
                                        </button>
                                    </form>
                                @else
                                    <span class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 mt-0.5
                                                 {{ $item->is_completed ? 'border-transparent' : 'border-gray-300 dark:border-slate-600' }}"
                                          @if($item->is_completed) style="background:var(--brand-500);" @endif>
                                        @if($item->is_completed)
                                            <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                        @endif
                                    </span>
                                @endif

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm {{ $item->is_completed ? 'line-through text-gray-400' : 'text-gray-700 dark:text-slate-200' }}">
                                        {{ $item->item }}
                                    </p>
                                    @if($item->is_completed && $item->completed_at)
                                        <p class="text-[10px] text-gray-400 mt-0.5">
                                            Completed {{ $item->completed_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Attachments --}}
            @if($task->attachments->isNotEmpty())
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i data-lucide="paperclip" class="w-3.5 h-3.5"></i> Attachments
                        <span class="font-mono text-[10px] text-gray-300 ml-1">({{ $task->attachments->count() }})</span>
                    </h3>
                    <div class="grid sm:grid-cols-2 gap-2">
                        @foreach($task->attachments as $att)
                            @php
                                $isImage = preg_match('/^image\//', $att->mime_type ?? '');
                                $sizeKb = round(($att->file_size ?? 0) / 1024);
                                $sizeLabel = $sizeKb > 1024 ? round($sizeKb/1024, 1) . ' MB' : $sizeKb . ' KB';
                            @endphp
                            <a href="{{ route('employee.tasks.attachment', [$tenantSlug, $task->id, $att->id]) }}"
                               class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                            {{ $isImage ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/10' : 'bg-gray-50 text-gray-500 dark:bg-slate-700' }}">
                                    <i data-lucide="{{ $isImage ? 'image' : 'file-text' }}" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-slate-100 truncate">{{ $att->file_name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono">{{ $sizeLabel }}</p>
                                </div>
                                <i data-lucide="download" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Comments --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> Comments
                    <span class="font-mono text-[10px] text-gray-300 ml-1">({{ $task->comments->count() }})</span>
                </h3>

                @if($task->comments->isEmpty())
                    <p class="text-xs text-gray-400 mb-4">No comments yet. Be the first to start the discussion.</p>
                @else
                    <div class="space-y-4 mb-5">
                        @foreach($task->comments as $comment)
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black text-white flex-shrink-0"
                                     style="background:var(--brand-500);">
                                    {{ strtoupper(substr($comment->user?->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0 bg-gray-50 dark:bg-slate-800/60 rounded-xl p-3">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="font-bold text-xs text-gray-900 dark:text-slate-100">{{ $comment->user?->name ?? 'Unknown' }}</span>
                                        <span class="text-[10px] text-gray-400 font-semibold">{{ $comment->created_at->diffForHumans() }}</span>
                                        @if($comment->is_edited)
                                            <span class="text-[9px] text-gray-400 italic">(edited)</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-700 dark:text-slate-200 whitespace-pre-line leading-relaxed">{!! nl2br(e($comment->content)) !!}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add comment form --}}
                <form action="{{ route('employee.tasks.comment', [$tenantSlug, $task->id]) }}" method="POST"
                      x-data="{ submitting: false }" @submit="submitting=true">
                    @csrf
                    <textarea name="content" required minlength="1" maxlength="5000" rows="3"
                              placeholder="Write a comment…"
                              class="lmt-textarea"></textarea>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="submit" class="lmt-btn-primary lmt-btn-sm" :disabled="submitting">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            <span x-show="!submitting">Comment</span>
                            <span x-show="submitting">Posting…</span>
                        </button>
                    </div>
                    @error('content') <p class="lmt-err">{{ $message }}</p> @enderror
                </form>
            </div>
        </div>

        {{-- ═════ RIGHT: details + assignees + activity ═════ --}}
        <div class="space-y-5">

            {{-- Details --}}
            <div class="lmt-card" data-lmt-anim="fade-up">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i> Details
                </h3>
                <div class="space-y-3">
                    @if($task->due_date)
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                <span>Due date</span>
                            </div>
                            <span class="text-sm font-semibold {{ $isOverdue ? 'text-red-600' : 'text-gray-900 dark:text-slate-100' }} text-right">
                                {{ $task->due_date->format('M j, Y') }}
                                @if($task->due_date->format('H:i') !== '00:00')
                                    <span class="text-xs text-gray-400 ml-1">{{ $task->due_date->format('g:i A') }}</span>
                                @endif
                            </span>
                        </div>
                    @endif
                    @if($task->start_date)
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                <span>Start date</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $task->start_date->format('M j, Y') }}</span>
                        </div>
                    @endif
                    @if($task->estimated_hours > 0)
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                <span>Estimated</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 font-mono">{{ number_format($task->estimated_hours, 1) }}h</span>
                        </div>
                    @endif
                    @if($task->logged_hours > 0)
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="timer" class="w-3.5 h-3.5"></i>
                                <span>Logged</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 font-mono">{{ number_format($task->logged_hours, 1) }}h</span>
                        </div>
                    @endif
                    @if($task->taskList)
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="columns-3" class="w-3.5 h-3.5"></i>
                                <span>Column</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $task->taskList->name }}</span>
                        </div>
                    @endif
                    @if($task->parent)
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="git-branch" class="w-3.5 h-3.5"></i>
                                <span>Parent task</span>
                            </div>
                            <a href="{{ route('employee.tasks.show', [$tenantSlug, $task->parent->id]) }}"
                               class="text-sm font-semibold hover:underline" style="color:var(--brand-600);">
                                {{ $task->parent->task_code }}
                            </a>
                        </div>
                    @endif
                    @if($task->completed_at)
                        <div class="flex items-start justify-between gap-3 py-1.5 border-b border-gray-50 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                <span>Completed</span>
                            </div>
                            <span class="text-sm font-semibold text-emerald-600">{{ $task->completed_at->format('M j, Y') }}</span>
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-3 py-1.5">
                        <div class="flex items-center gap-2 text-gray-500 dark:text-slate-400 text-xs">
                            <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                            <span>Created</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $task->created_at->format('M j, Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Assignees --}}
            @if($task->assignees->isNotEmpty())
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i> Assignees
                    </h3>
                    <div class="space-y-2">
                        @foreach($task->assignees as $a)
                            @if($a->employee)
                                <div class="flex items-center gap-2.5 p-2 rounded-xl bg-gray-50/70 dark:bg-slate-800/50">
                                    <img src="{{ $a->employee->avatar_url }}" alt="{{ $a->employee->full_name }}"
                                         class="w-8 h-8 rounded-full ring-2 ring-white dark:ring-slate-700 object-cover"/>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate">
                                            {{ $a->employee->full_name }}
                                            @if($a->employee_id === $emp->id)
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded ml-1" style="background:var(--brand-50);color:var(--brand-600);">YOU</span>
                                            @endif
                                        </p>
                                        @if($a->is_primary)
                                            <p class="text-[10px] text-gray-400 font-bold uppercase">Primary</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Activity log --}}
            @if($activities->isNotEmpty())
                <div class="lmt-card" data-lmt-anim="fade-up">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i data-lucide="git-commit" class="w-3.5 h-3.5"></i> Activity
                    </h3>
                    <div class="relative">
                        @foreach($activities as $i => $a)
                            @php
                                $isLast = $i === count($activities) - 1;
                                $iconMap = [
                                    'created'          => ['file-plus',     '#f3f4f6', '#6b7280'],
                                    'status_changed'  => ['arrow-right-left','var(--brand-50)', 'var(--brand-600)'],
                                    'progress_changed'=> ['trending-up',    '#ecfdf5', '#10b981'],
                                    'commented'       => ['message-circle', '#f3f4f6', '#6b7280'],
                                    'assigned'        => ['user-plus',      '#fffbeb', '#d97706'],
                                    'unassigned'      => ['user-minus',     '#fffbeb', '#d97706'],
                                    'completed'       => ['check-circle',   '#ecfdf5', '#10b981'],
                                    'attachment_added'=> ['paperclip',      '#f3f4f6', '#6b7280'],
                                ];
                                [$ico, $bg, $fg] = $iconMap[$a->action] ?? ['circle', '#f3f4f6', '#6b7280'];
                            @endphp
                            <div class="relative flex gap-3 pb-3 last:pb-0">
                                @if(! $isLast)
                                    <div class="absolute left-[11px] top-7 bottom-0 w-px bg-gray-200 dark:bg-slate-700"></div>
                                @endif
                                <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 relative z-10"
                                     style="background:{{ $bg }};color:{{ $fg }};">
                                    <i data-lucide="{{ $ico }}" class="w-3 h-3"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-700 dark:text-slate-200">{!! $a->summary !!}</p>
                                    <p class="text-[10px] text-gray-400 font-semibold">{{ $a->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function taskDetail() {
    return {
        progress: {{ (int) $task->progress }},
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection