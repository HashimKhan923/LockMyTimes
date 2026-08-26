@extends('layouts.admin')
@section('title', $project->name . ' — Board')
@section('page-title', $project->name)

@push('head')
<style>
/* =====================================================
   KANBAN BOARD STYLES
===================================================== */
.kanban-wrapper {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1.5rem;
    min-height: calc(100vh - 220px);
    align-items: flex-start;
}

.kanban-col {
    flex: 0 0 300px;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 220px);
}

.kanban-col-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .75rem 1rem;
    border-radius: .875rem .875rem 0 0;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-bottom: none;
    position: sticky;
    top: 0;
    z-index: 2;
}

.kanban-col-body {
    flex: 1;
    overflow-y: auto;
    padding: .5rem;
    border: 1.5px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 .875rem .875rem;
    background: #f8fafc;
    min-height: 120px;
}

.kanban-col-body.drag-over {
    background: #EEF2FF;
    border-color: #6C7DF7;
}

/* Task card */
.task-card {
    background: #fff;
    border-radius: .75rem;
    padding: .875rem;
    margin-bottom: .5rem;
    border: 1.5px solid #e2e8f0;
    cursor: grab;
    transition: all .15s ease;
    position: relative;
}
.task-card:hover {
    border-color: #6C7DF7;
    box-shadow: 0 4px 12px rgba(108,125,247,.12);
    transform: translateY(-1px);
}
.task-card.dragging {
    opacity: .5;
    cursor: grabbing;
    transform: rotate(2deg) scale(1.02);
    box-shadow: 0 12px 32px rgba(0,0,0,.15);
    z-index: 999;
}
.task-card.drag-placeholder {
    background: #EEF2FF;
    border: 2px dashed #6C7DF7;
    min-height: 80px;
}

/* Priority indicators */
.priority-urgent { border-left: 3px solid #EF4444; }
.priority-high    { border-left: 3px solid #F59E0B; }
.priority-normal  { border-left: 3px solid #6C7DF7; }
.priority-low     { border-left: 3px solid #94A3B8; }

/* Scrollbar */
.kanban-col-body::-webkit-scrollbar { width: 4px; }
.kanban-col-body::-webkit-scrollbar-track { background: transparent; }
.kanban-col-body::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
.kanban-wrapper::-webkit-scrollbar { height: 6px; }
.kanban-wrapper::-webkit-scrollbar-track { background: transparent; }
.kanban-wrapper::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }

/* Task type badges */
.badge-bug         { background:#FEE2E2; color:#DC2626; }
.badge-feature     { background:#D1FAE5; color:#059669; }
.badge-epic        { background:#EDE9FE; color:#7C3AED; }
.badge-story       { background:#FEF3C7; color:#D97706; }
.badge-improvement { background:#DBEAFE; color:#2563EB; }
.badge-support     { background:#F0FDF4; color:#16A34A; }
.badge-task        { background:#F1F5F9; color:#475569; }

/* Task detail modal */
#task-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.45);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 2rem 1rem;
    overflow-y: auto;
}
/* ID selector beats .hidden — explicitly re-hide when class is present */
#task-modal-backdrop.hidden { display: none; }
#task-modal {
    background: #fff;
    border-radius: 1.25rem;
    width: 100%;
    max-width: 720px;
    box-shadow: 0 25px 60px rgba(0,0,0,.2);
    overflow: hidden;
    position: relative;
}
</style>
@endpush

@section('content')

{{-- Project header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.projects.index', $tenant) }}"
           class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center text-gray-800 hover:bg-gray-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black"
             style="background:{{ $project->color }}">
            {{ substr($project->name, 0, 1) }}
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="font-black text-gray-900 text-lg">{{ $project->name }}</h1>
                <span class="lmt-badge-gray text-xs capitalize">{{ str_replace('_',' ',$project->status) }}</span>
            </div>
            <p class="text-xs text-gray-800">{{ $project->code }}
                @if($project->manager) · PM: {{ $project->manager->full_name }} @endif
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        {{-- Task stats --}}
        @foreach([
            ['done', $taskStats['done'], 'text-emerald-600'],
            ['in progress', $taskStats['in_progress'], 'text-brand-600'],
            ['overdue', $taskStats['overdue'], 'text-red-500'],
        ] as [$label, $count, $color])
        <div class="bg-gray-100 rounded-xl px-3 py-1.5 text-center">
            <p class="text-sm font-black {{ $color }}">{{ $count }}</p>
            <p class="text-[10px] text-gray-800">{{ $label }}</p>
        </div>
        @endforeach

        {{-- Members avatars --}}
        <div class="flex -space-x-2">
            @foreach($project->members->take(5) as $member)
            <div class="w-8 h-8 rounded-full border-2 border-white lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold"
                 title="{{ $member->employee->full_name }}">
                {{ substr($member->employee->first_name ?? '?', 0, 1) }}
            </div>
            @endforeach
        </div>

        <button onclick="document.getElementById('add-task-modal').classList.remove('hidden');document.getElementById('add-task-modal').classList.add('flex');"
                class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Task
        </button>

        <button onclick="openAddColumnModal()"
                class="w-9 h-9 rounded-xl bg-gray-100 text-gray-800 hover:bg-gray-200 flex items-center justify-center transition-colors"
                title="Add Column">
            <i data-lucide="columns" class="w-4 h-4"></i>
        </button>
    </div>
</div>

{{-- ===== KANBAN BOARD ===== --}}
<div class="kanban-wrapper" id="kanban-board">

    @foreach($taskLists as $list)
    <div class="kanban-col" data-list-id="{{ $list->id }}">

        {{-- Column header --}}
        <div class="kanban-col-header">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" style="background:{{ $list->color }}"></div>
                <span class="font-bold text-gray-800 text-sm">{{ $list->name }}</span>
                <span class="w-5 h-5 rounded-full bg-gray-200 text-gray-800 text-xs font-bold flex items-center justify-center">
                    {{ $list->tasks->count() }}
                </span>
            </div>
            <div class="flex items-center gap-1">
                @if($list->wip_limit)
                <span class="text-xs text-amber-600 font-semibold">WIP: {{ $list->wip_limit }}</span>
                @endif
                <button onclick="quickAddTask({{ $list->id }})"
                        class="w-6 h-6 rounded-lg text-gray-800 hover:text-brand-600 hover:bg-brand-50 flex items-center justify-center transition-colors"
                        title="Quick Add">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                </button>
                <button onclick="deleteColumn({{ $list->id }}, this)"
                        class="w-6 h-6 rounded-lg text-gray-800 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors"
                        title="Delete Column">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        {{-- Tasks drop zone --}}
        <div class="kanban-col-body"
             id="col-{{ $list->id }}"
             data-list-id="{{ $list->id }}"
             ondragover="onDragOver(event)"
             ondrop="onDrop(event)"
             ondragleave="onDragLeave(event)">

            @foreach($list->tasks as $task)
            <div class="task-card priority-{{ $task->priority }}"
                 id="task-{{ $task->id }}"
                 data-task-id="{{ $task->id }}"
                 data-list-id="{{ $list->id }}"
                 draggable="true"
                 ondragstart="onDragStart(event)"
                 ondragend="onDragEnd(event)"
                 ondragover="event.preventDefault()"
                 ondrop="onDrop(event)"
                 onclick="openTaskModal({{ $task->id }})">

                {{-- Type badge --}}
                <div class="flex items-center justify-between mb-2">
                    <span class="badge-{{ $task->type }} text-[10px] font-bold px-2 py-0.5 rounded-full capitalize">
                        {{ $task->type }}
                    </span>
                    <div class="flex items-center gap-1">
                        @if($task->due_date)
                        <span class="text-[10px] {{ $task->is_overdue ? 'text-red-500 font-bold' : 'text-gray-800' }} flex items-center gap-0.5">
                            <i data-lucide="calendar" class="w-2.5 h-2.5"></i>
                            {{ $task->due_date->format('M j') }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Title --}}
                <p class="text-sm font-semibold text-gray-900 leading-snug mb-2 line-clamp-2">
                    {{ $task->title }}
                </p>

                {{-- Progress bar (if > 0) --}}
                @if($task->progress > 0)
                <div class="h-1 bg-gray-100 rounded-full overflow-hidden mb-2">
                    <div class="h-full rounded-full"
                         style="width:{{ $task->progress }}%; background:{{ $list->color }}"></div>
                </div>
                @endif

                {{-- Footer --}}
                <div class="flex items-center justify-between mt-2">
                    {{-- Assignees --}}
                    <div class="flex -space-x-1.5 card-assignees">
                        @foreach($task->assignees->take(3) as $assignee)
                        <div class="w-6 h-6 rounded-full border-2 border-white lmt-gradient-bg flex items-center justify-center text-white text-[9px] font-bold"
                             title="{{ $assignee->employee->full_name ?? '' }}">
                            {{ substr($assignee->employee->first_name ?? '?', 0, 1) }}
                        </div>
                        @endforeach
                    </div>

                    {{-- Meta icons --}}
                    <div class="flex items-center gap-2 text-gray-800">
                        @if($task->comments_count > 0)
                        <span class="flex items-center gap-0.5 text-[10px]">
                            <i data-lucide="message-circle" class="w-3 h-3"></i>
                            {{ $task->comments_count }}
                        </span>
                        @endif
                        @if($task->subtasks_count > 0)
                        <span class="flex items-center gap-0.5 text-[10px] {{ $task->completed_subtasks_count >= $task->subtasks_count ? 'text-emerald-500' : '' }}">
                            <i data-lucide="check-square" class="w-3 h-3"></i>
                            {{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}
                        </span>
                        @endif
                        @if($task->estimated_hours > 0)
                        <span class="flex items-center gap-0.5 text-[10px]">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            {{ $task->estimated_hours }}h
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Quick add inline form --}}
            <div id="quick-add-{{ $list->id }}" class="hidden">
                <div class="bg-white rounded-xl border-2 border-brand-300 p-3 mt-1">
                    <textarea id="quick-title-{{ $list->id }}"
                              class="w-full text-sm text-gray-900 resize-none border-none outline-none bg-transparent"
                              rows="2" placeholder="Task title…"></textarea>
                    <div class="flex gap-2 mt-2">
                        <button onclick="submitQuickAdd({{ $list->id }})"
                                class="lmt-btn-primary lmt-btn-sm">Add</button>
                        <button onclick="cancelQuickAdd({{ $list->id }})"
                                class="lmt-btn-secondary lmt-btn-sm">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Add column button --}}
    <div class="flex-shrink-0 w-64">
        <button onclick="openAddColumnModal()"
                class="w-full h-12 rounded-xl border-2 border-dashed border-gray-300 text-gray-800 text-sm font-semibold hover:border-brand-400 hover:text-brand-600 hover:bg-brand-50 transition-all flex items-center justify-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Column
        </button>
    </div>
</div>

{{-- =====================================================
     TASK DETAIL MODAL
===================================================== --}}
<div id="task-modal-backdrop" class="hidden" onclick="if(event.target===this) closeTaskModal()">
    <div id="task-modal">
        {{-- Modal header with color bar --}}
        <div id="task-modal-header" class="h-1.5 w-full"></div>

        <div class="p-6">
            {{-- Loading state --}}
            <div id="task-modal-loading" class="text-center py-10">
                <div class="w-8 h-8 border-4 border-brand-200 border-t-brand-600 rounded-full animate-spin mx-auto"></div>
                <p class="text-sm text-gray-800 mt-3">Loading task…</p>
            </div>

            {{-- Task content --}}
            <div id="task-modal-content" class="hidden">

                {{-- Type + Close --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span id="tmd-type" class="text-xs font-bold px-2 py-1 rounded-full"></span>
                        <span id="tmd-code" class="text-xs text-gray-800 font-mono"></span>
                    </div>
                    <button onclick="closeTaskModal()"
                            class="w-8 h-8 rounded-lg text-gray-800 hover:text-gray-800 hover:bg-gray-100 flex items-center justify-center transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                {{-- Title --}}
                <input id="tmd-title" type="text"
                       class="w-full text-xl font-black text-gray-900 mb-3 leading-tight border-0 border-b-2 border-transparent focus:border-brand-400 focus:outline-none bg-transparent py-1 transition-colors"
                       placeholder="Task title…"/>

                <div class="grid grid-cols-3 gap-5">
                    {{-- Left — main content --}}
                    <div class="col-span-2 space-y-5">

                        {{-- Description --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-2">Description</p>
                            <textarea id="tmd-description" rows="3"
                                      class="lmt-textarea text-sm w-full"
                                      placeholder="Add a description…"></textarea>
                        </div>

                        {{-- Attachments --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-2">Attachments</p>
                            <div id="tmd-attachments" class="space-y-1.5 mb-2"></div>
                            <label class="flex items-center gap-2 cursor-pointer w-fit">
                                <input type="file" id="attachment-input" class="hidden" multiple
                                       onchange="uploadAttachments(this)"/>
                                <span class="lmt-btn-secondary lmt-btn-sm">
                                    <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                    Attach File
                                </span>
                            </label>
                        </div>

                        {{-- Checklist --}}
                        <div id="tmd-checklist-section">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-bold text-gray-800 uppercase tracking-wider">Checklist</p>
                                <span id="tmd-checklist-progress" class="text-xs font-bold text-gray-800"></span>
                            </div>
                            <div id="tmd-checklist" class="space-y-1.5 mb-3"></div>
                            <div class="flex gap-2">
                                <input type="text" id="checklist-input"
                                       class="lmt-input py-2 text-sm flex-1"
                                       placeholder="Add checklist item…"
                                       onkeydown="if(event.key==='Enter'){event.preventDefault();addChecklistItem();}"/>
                                <button onclick="addChecklistItem()" class="lmt-btn-secondary lmt-btn-sm">Add</button>
                            </div>
                        </div>

                        {{-- Comments --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">Comments</p>
                            <div id="tmd-comments" class="space-y-3 max-h-48 overflow-y-auto mb-3"></div>
                            <div class="flex gap-2">
                                <textarea id="comment-input" rows="2"
                                          class="lmt-textarea flex-1 text-sm py-2"
                                          placeholder="Write a comment…"></textarea>
                                <button onclick="submitComment()" class="lmt-btn-primary lmt-btn-sm self-end">Post</button>
                            </div>
                        </div>
                    </div>

                    {{-- Right — meta --}}
                    <div class="space-y-4">

                        {{-- Status --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Status</p>
                            <select id="tmd-status" onchange="updateTaskField('status', this.value)"
                                    class="lmt-select py-2 text-sm w-full">
                                @foreach(['backlog'=>'Backlog','todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','on_hold'=>'On Hold','done'=>'Done','cancelled'=>'Cancelled'] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Priority</p>
                            <select id="tmd-priority" onchange="updateTaskField('priority', this.value)"
                                    class="lmt-select py-2 text-sm w-full">
                                @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assignees --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-2">Assignees</p>
                            <div id="tmd-assignees" class="flex flex-wrap gap-1.5 mb-2"></div>
                            {{-- Assignee picker dropdown --}}
                            <div class="relative" id="assignee-picker-wrap">
                                <button type="button" onclick="toggleAssigneePicker()"
                                        class="w-full flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-dashed border-gray-300 text-xs text-gray-800 hover:border-brand-400 hover:text-brand-600 transition-colors">
                                    <i data-lucide="user-plus" class="w-3 h-3"></i>
                                    Assign member
                                </button>
                                <div id="assignee-picker-dropdown"
                                     class="absolute z-50 top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-200 hidden max-h-48 overflow-y-auto">
                                    @foreach($employees as $emp)
                                    <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" class="assignee-cb w-3.5 h-3.5 rounded"
                                               value="{{ $emp->id }}"
                                               data-name="{{ $emp->full_name }}"
                                               onchange="syncAssignees()"/>
                                        <div class="w-6 h-6 rounded-full bg-brand-100 text-brand-700 text-[10px] font-black flex items-center justify-center flex-shrink-0">
                                            {{ substr($emp->first_name, 0, 1) }}
                                        </div>
                                        <span class="text-xs text-gray-800 truncate">{{ $emp->full_name }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Due Date --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Due Date</p>
                            <input type="date" id="tmd-due" class="lmt-input py-1.5 text-sm w-full"/>
                        </div>

                        {{-- Estimated Hours --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Estimated Hours</p>
                            <input type="number" id="tmd-hours" min="0" step="0.5"
                                   class="lmt-input py-1.5 text-sm w-full" placeholder="0"/>
                        </div>

                        {{-- Progress --}}
                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Progress</p>
                            <div class="flex items-center gap-2">
                                <input type="range" id="tmd-progress" min="0" max="100" step="5"
                                       class="flex-1 h-2 accent-brand-600"
                                       oninput="document.getElementById('tmd-progress-val').textContent=this.value+'%'"/>
                                <span id="tmd-progress-val" class="text-xs font-bold text-gray-800 w-8">0%</span>
                            </div>
                        </div>

                        {{-- Save --}}
                        <button onclick="saveTaskEdits()"
                                class="w-full lmt-btn-primary lmt-btn-sm">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            Save Changes
                        </button>

                        {{-- Delete --}}
                        <button onclick="deleteTask()"
                                class="w-full lmt-btn-danger lmt-btn-sm">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            Delete Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Task Modal --}}
<div id="add-task-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">New Task</h3>
        <form id="add-task-form" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Column <span class="text-red-500">*</span></label>
                <select id="task-list-select" name="task_list_id" required class="lmt-select">
                    @foreach($taskLists as $list)
                    <option value="{{ $list->id }}">{{ $list->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Title <span class="text-red-500">*</span></label>
                <input type="text" id="task-title" name="title" required class="lmt-input"
                       placeholder="What needs to be done?"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Type</label>
                    <select name="type" id="task-type" class="lmt-select">
                        @foreach(['task'=>'Task','bug'=>'Bug','feature'=>'Feature','epic'=>'Epic','story'=>'Story','improvement'=>'Improvement','support'=>'Support'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Priority</label>
                    <select name="priority" id="task-priority" class="lmt-select">
                        @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                        <option value="{{ $v }}" {{ $v==='normal'?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Due Date</label>
                    <input type="date" name="due_date" id="task-due" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Estimated (hours)</label>
                    <input type="number" name="estimated_hours" id="task-hours" step="0.5" min="0" class="lmt-input" placeholder="0"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Assign To</label>
                <div class="rounded-xl border border-gray-200 max-h-36 overflow-y-auto divide-y divide-gray-50" id="new-task-assignees-list">
                    @foreach($employees as $emp)
                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" class="new-task-assignee-cb w-3.5 h-3.5 rounded"
                               value="{{ $emp->id }}"/>
                        <div class="w-6 h-6 rounded-full bg-brand-100 text-brand-700 text-[10px] font-black flex items-center justify-center flex-shrink-0">
                            {{ substr($emp->first_name, 0, 1) }}
                        </div>
                        <span class="text-sm text-gray-800">{{ $emp->full_name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" id="task-desc" class="lmt-textarea" rows="2" placeholder="Optional details…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="submitNewTask()" class="lmt-btn-primary flex-1">Create Task</button>
                <button type="button" onclick="closeModal('add-task-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Column Modal --}}
<div id="add-col-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-sm">
        <h3 class="font-black text-gray-900 mb-5">Add Column</h3>
        <div class="space-y-4">
            <div>
                <label class="lmt-label">Column Name <span class="text-red-500">*</span></label>
                <input type="text" id="new-col-name" class="lmt-input" placeholder="e.g. Testing"/>
            </div>
            <div>
                <label class="lmt-label">Color</label>
                <input type="color" id="new-col-color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
            </div>
            <div class="flex gap-3">
                <button onclick="submitNewColumn()" class="lmt-btn-primary flex-1">Add Column</button>
                <button onclick="closeModal('add-col-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const TENANT   = '{{ $tenant }}';
const PROJECT_ID = {{ $project->id }};
const BASE_URL = `/t/${TENANT}/admin/projects/${PROJECT_ID}`;
const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

let dragTask     = null;
let dragSource   = null;
let currentTaskId = null;

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

/* =====================================================
   DRAG & DROP
===================================================== */
function onDragStart(e) {
    dragTask   = e.currentTarget;
    dragSource = dragTask.closest('.kanban-col-body');
    dragTask.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', dragTask.dataset.taskId);
}

function onDragEnd(e) {
    dragTask?.classList.remove('dragging');
    document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
    document.querySelectorAll('.drag-placeholder').forEach(el => el.remove());
    dragTask = null;
    dragSource = null;
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const col = e.currentTarget;
    col.classList.add('drag-over');

    // Show placeholder at correct position
    const afterEl = getDragAfterElement(col, e.clientY);
    const placeholder = document.querySelector('.drag-placeholder');

    if (placeholder) placeholder.remove();

    const ph = document.createElement('div');
    ph.className = 'task-card drag-placeholder';

    if (!afterEl) {
        col.appendChild(ph);
    } else {
        col.insertBefore(ph, afterEl);
    }
}

function onDragLeave(e) {
    if (!e.currentTarget.contains(e.relatedTarget)) {
        e.currentTarget.classList.remove('drag-over');
        document.querySelectorAll('.drag-placeholder').forEach(el => el.remove());
    }
}

function onDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    // Normalise — handler may fire on a task card (ondrop) or on the col-body
    const col = e.currentTarget.classList.contains('kanban-col-body')
        ? e.currentTarget
        : e.currentTarget.closest('.kanban-col-body');
    if (!col) return;
    const newListId= col.dataset.listId;
    const taskId   = e.dataTransfer.getData('text/plain');
    const taskEl   = document.getElementById(`task-${taskId}`);

    col.classList.remove('drag-over');
    document.querySelectorAll('.drag-placeholder').forEach(el => el.remove());

    if (!taskEl) return;

    // Move the DOM element
    const afterEl = getDragAfterElement(col, e.clientY);
    if (!afterEl) {
        col.appendChild(taskEl);
    } else {
        col.insertBefore(taskEl, afterEl);
    }

    taskEl.dataset.listId = newListId;

    // Get ordered IDs in the new column
    const orderedIds = [...col.querySelectorAll('.task-card[data-task-id]')]
        .map(el => el.dataset.taskId);

    // Persist via AJAX
    fetch(`${BASE_URL}/tasks/${taskId}/move`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            task_list_id: newListId,
            sort_order: orderedIds.indexOf(taskId),
            ordered_ids: orderedIds,
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update column task counts
            updateColCounts();
            if (window.lucide) lucide.createIcons();
        }
    })
    .catch(console.error);
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.task-card:not(.dragging):not(.drag-placeholder)')];
    return draggableElements.reduce((closest, child) => {
        const box    = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > (closest.offset ?? -Infinity)) {
            return { offset, element: child };
        }
        return closest;
    }, {}).element ?? null;
}

function updateColCounts() {
    document.querySelectorAll('.kanban-col').forEach(col => {
        const listId = col.dataset.listId;
        const count  = col.querySelectorAll('.task-card[data-task-id]').length;
        const badge  = col.querySelector('.kanban-col-header .w-5');
        if (badge) badge.textContent = count;
    });
}

/* =====================================================
   QUICK ADD
===================================================== */
function quickAddTask(listId) {
    document.querySelectorAll('[id^="quick-add-"]').forEach(el => el.classList.add('hidden'));
    const el = document.getElementById(`quick-add-${listId}`);
    el.classList.remove('hidden');
    document.getElementById(`quick-title-${listId}`).focus();
}

function cancelQuickAdd(listId) {
    document.getElementById(`quick-add-${listId}`).classList.add('hidden');
}

async function submitQuickAdd(listId) {
    const title = document.getElementById(`quick-title-${listId}`).value.trim();
    if (!title) return;

    const res = await apiFetch(`${BASE_URL}/tasks`, 'POST', {
        task_list_id: listId,
        title,
        type: 'task',
        priority: 'normal',
    });

    if (res.success) {
        addTaskCardToDOM(res.task, listId);
        document.getElementById(`quick-title-${listId}`).value = '';
        cancelQuickAdd(listId);
        updateColCounts();
        if (window.lucide) lucide.createIcons();
    }
}

/* =====================================================
   ADD TASK MODAL
===================================================== */
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

async function submitNewTask() {
    const form    = document.getElementById('add-task-form');
    const listId  = document.getElementById('task-list-select').value;
    const title   = document.getElementById('task-title').value.trim();
    if (!title) return;

    const assigneeIds = [...document.querySelectorAll('.new-task-assignee-cb:checked')].map(cb => cb.value);

    const res = await apiFetch(`${BASE_URL}/tasks`, 'POST', {
        task_list_id:    listId,
        title,
        type:            document.getElementById('task-type').value,
        priority:        document.getElementById('task-priority').value,
        due_date:        document.getElementById('task-due').value || null,
        estimated_hours: document.getElementById('task-hours').value || 0,
        description:     document.getElementById('task-desc').value || null,
        assignee_ids:    assigneeIds,
    });

    if (res.success) {
        addTaskCardToDOM(res.task, listId);
        closeModal('add-task-modal');
        form.reset();
        document.querySelectorAll('.new-task-assignee-cb').forEach(cb => cb.checked = false);
        updateColCounts();
        if (window.lucide) lucide.createIcons();
    }
}

function addTaskCardToDOM(task, listId) {
    const col = document.getElementById(`col-${listId}`);
    if (!col) return;

    const card = document.createElement('div');
    card.className = `task-card priority-${task.priority}`;
    card.id        = `task-${task.id}`;
    card.dataset.taskId  = task.id;
    card.dataset.listId  = listId;
    card.draggable = true;
    card.addEventListener('dragstart', onDragStart);
    card.addEventListener('dragend', onDragEnd);
    card.addEventListener('click', () => openTaskModal(task.id));

    card.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <span class="badge-${task.type} text-[10px] font-bold px-2 py-0.5 rounded-full capitalize">${task.type}</span>
        </div>
        <p class="text-sm font-semibold text-gray-900 leading-snug mb-2">${task.title}</p>
        <div class="flex items-center justify-between mt-2">
            <div class="flex -space-x-1.5 card-assignees">
                ${(task.assignees||[]).slice(0,3).map(a=>`<div class="w-6 h-6 rounded-full border-2 border-white lmt-gradient-bg flex items-center justify-center text-white text-[9px] font-bold" title="${a.employee?.full_name??''}">${(a.employee?.first_name??'?')[0]}</div>`).join('')}
            </div>
            <div class="flex items-center gap-2 text-gray-800"></div>
        </div>`;

    // Insert before quick-add form
    const qa = document.getElementById(`quick-add-${listId}`);
    col.insertBefore(card, qa);
}

/* =====================================================
   TASK DETAIL MODAL
===================================================== */
async function openTaskModal(taskId) {
    currentTaskId = taskId;
    document.getElementById('task-modal-backdrop').classList.remove('hidden');
    document.getElementById('task-modal-loading').classList.remove('hidden');
    document.getElementById('task-modal-content').classList.add('hidden');

    const task = await apiFetch(`${BASE_URL}/tasks/${taskId}`, 'GET');
    renderTaskModal(task);
}

function closeTaskModal() {
    document.getElementById('task-modal-backdrop').classList.add('hidden');
    currentTaskId = null;
}

function renderTaskModal(task) {
    // Color bar
    const priorityColors = {urgent:'#EF4444',high:'#F59E0B',normal:'#6C7DF7',low:'#94A3B8'};
    document.getElementById('task-modal-header').style.background = priorityColors[task.priority] ?? '#6C7DF7';

    document.getElementById('tmd-code').textContent   = task.task_code;
    document.getElementById('tmd-title').value        = task.title ?? '';
    document.getElementById('tmd-status').value       = task.status ?? 'todo';
    document.getElementById('tmd-priority').value     = task.priority ?? 'normal';
    document.getElementById('tmd-description').value  = task.description ?? '';
    document.getElementById('tmd-hours').value        = task.estimated_hours > 0 ? task.estimated_hours : '';
    document.getElementById('tmd-due').value          = task.due_date ? task.due_date.substring(0, 10) : '';
    const prog = task.progress ?? 0;
    document.getElementById('tmd-progress').value     = prog;
    document.getElementById('tmd-progress-val').textContent = `${prog}%`;

    // Type badge
    const typeBadge = document.getElementById('tmd-type');
    typeBadge.className = `badge-${task.type} text-xs font-bold px-2 py-0.5 rounded-full capitalize`;
    typeBadge.textContent = task.type;

    // Assignees — reset all checkboxes then check the current ones
    const currentAssigneeIds = (task.assignees || []).map(a => String(a.employee_id));
    document.querySelectorAll('.assignee-cb').forEach(cb => {
        cb.checked = currentAssigneeIds.includes(cb.value);
    });
    document.getElementById('assignee-picker-dropdown')?.classList.add('hidden');
    syncAssignees();

    // Checklist
    renderChecklist(task.checklists || []);

    // Comments
    renderComments(task.comments || []);

    // Attachments
    renderAttachments(task.attachments || []);

    document.getElementById('task-modal-loading').classList.add('hidden');
    document.getElementById('task-modal-content').classList.remove('hidden');

    if (window.lucide) lucide.createIcons();
}

function renderChecklist(items) {
    const container = document.getElementById('tmd-checklist');
    const progress  = document.getElementById('tmd-checklist-progress');
    container.innerHTML = '';

    if (items.length === 0) {
        progress.textContent = '';
        return;
    }

    const done = items.filter(i => i.is_completed).length;
    progress.textContent = `${done}/${items.length}`;

    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 group';
        div.innerHTML = `
            <input type="checkbox" ${item.is_completed ? 'checked' : ''}
                   onchange="toggleChecklist(${item.id}, this.checked)"
                   class="w-4 h-4 rounded cursor-pointer"/>
            <span class="text-sm text-gray-800 flex-1 ${item.is_completed ? 'line-through text-gray-800' : ''}">${item.item}</span>`;
        container.appendChild(div);
    });
}

function renderComments(comments) {
    const container = document.getElementById('tmd-comments');
    container.innerHTML = '';
    if (comments.length === 0) {
        container.innerHTML = '<p class="text-xs text-gray-800 text-center py-3">No comments yet</p>';
        return;
    }
    comments.forEach(c => {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2';
        div.innerHTML = `
            <div class="w-7 h-7 rounded-full lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                ${(c.user?.name ?? 'U')[0]}
            </div>
            <div class="flex-1 bg-gray-50 rounded-xl p-2.5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold text-gray-800">${c.user?.name ?? 'User'}</span>
                    <span class="text-[10px] text-gray-800">${new Date(c.created_at).toLocaleDateString()}</span>
                </div>
                <p class="text-sm text-gray-800">${c.content}</p>
            </div>`;
        container.appendChild(div);
    });
}

/* =====================================================
   TASK ACTIONS
===================================================== */
async function saveTaskEdits() {
    if (!currentTaskId) return;
    const assigneeIds = [...document.querySelectorAll('.assignee-cb:checked')].map(cb => cb.value);
    const payload = {
        title:           document.getElementById('tmd-title').value.trim(),
        description:     document.getElementById('tmd-description').value.trim() || null,
        due_date:        document.getElementById('tmd-due').value || null,
        estimated_hours: parseFloat(document.getElementById('tmd-hours').value) || 0,
        progress:        parseInt(document.getElementById('tmd-progress').value, 10),
        assignee_ids:    assigneeIds,
    };
    const res = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}`, 'PUT', payload);
    if (res.success) {
        const card = document.getElementById(`task-${currentTaskId}`);
        if (card) card.querySelector('p.text-sm') && (card.querySelector('p.text-sm').textContent = payload.title);
        // Refresh assignee pills on card
        renderCardAssignees(card, res.task?.assignees || []);
        const btn = document.querySelector('#task-modal-content button[onclick="saveTaskEdits()"]');
        if (btn) { btn.textContent = ' Saved'; setTimeout(() => { btn.innerHTML = '<i data-lucide="save" class="w-3.5 h-3.5 inline-block mr-1"></i>Save Changes'; if(window.lucide) lucide.createIcons(); }, 1500); }
    }
}

function renderCardAssignees(card, assignees) {
    if (!card) return;
    const wrap = card.querySelector('.card-assignees');
    if (!wrap) return;
    wrap.innerHTML = assignees.slice(0,3).map(a => {
        const name = a.employee?.first_name ?? '?';
        return `<div class="w-5 h-5 rounded-full bg-brand-100 text-brand-700 text-[9px] font-black flex items-center justify-center ring-1 ring-white" title="${a.employee?.full_name ?? ''}">${name[0]}</div>`;
    }).join('');
}

function toggleAssigneePicker() {
    const dd = document.getElementById('assignee-picker-dropdown');
    dd.classList.toggle('hidden');
}

function syncAssignees() {
    const checked = [...document.querySelectorAll('.assignee-cb:checked')];
    const el = document.getElementById('tmd-assignees');
    el.innerHTML = checked.length === 0
        ? '<span class="text-xs text-gray-800 italic">No assignees</span>'
        : checked.map(cb => `
            <div class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 text-xs font-bold">
                <div class="w-4 h-4 rounded-full bg-brand-200 text-brand-800 text-[9px] font-black flex items-center justify-center">${cb.dataset.name[0]}</div>
                ${cb.dataset.name.split(' ')[0]}
            </div>`).join('');
}

// Close assignee picker when clicking outside
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('assignee-picker-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('assignee-picker-dropdown')?.classList.add('hidden');
    }
});

async function updateTaskField(field, value) {
    if (!currentTaskId) return;
    await apiFetch(`${BASE_URL}/tasks/${currentTaskId}`, 'PUT', { [field]: value });
}

function renderAttachments(attachments) {
    const container = document.getElementById('tmd-attachments');
    container.innerHTML = '';
    if (!attachments.length) {
        container.innerHTML = '<p class="text-xs text-gray-800 italic">No attachments yet.</p>';
        return;
    }
    attachments.forEach(a => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 text-sm text-gray-800 bg-gray-50 rounded-lg px-3 py-1.5';
        div.innerHTML = `
            <i data-lucide="paperclip" class="w-3.5 h-3.5 text-gray-800 flex-shrink-0"></i>
            <a href="/storage/${a.file_path}" target="_blank" class="flex-1 truncate hover:text-brand-600">${a.file_name}</a>
            <span class="text-xs text-gray-800 flex-shrink-0">${a.file_size_human ?? ''}</span>
            <button onclick="deleteAttachment(${a.id}, this)" class="text-gray-800 hover:text-red-500 flex-shrink-0">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>`;
        container.appendChild(div);
    });
    if (window.lucide) lucide.createIcons();
}

async function uploadAttachments(input) {
    if (!currentTaskId || !input.files.length) return;
    const formData = new FormData();
    for (const file of input.files) formData.append('files[]', file);
    formData.append('_token', CSRF);

    try {
        const res = await fetch(`${BASE_URL}/tasks/${currentTaskId}/attachments`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: formData,
        });
        const data = await res.json();
        if (data.success) renderAttachments(data.attachments);
    } catch(err) { console.error(err); }
    input.value = '';
}

async function deleteAttachment(attachmentId, btn) {
    const res = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}/attachments/${attachmentId}`, 'DELETE');
    if (res.success) btn.closest('div').remove();
}

async function addChecklistItem() {
    if (!currentTaskId) return;
    const input = document.getElementById('checklist-input');
    const item  = input.value.trim();
    if (!item) return;

    const res = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}/checklist`, 'POST', { item });
    if (res.success) {
        input.value = '';
        const task = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}`, 'GET');
        renderChecklist(task.checklists || []);
    }
}

async function toggleChecklist(checklistId, checked) {
    if (!currentTaskId) return;
    const res = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}/checklist/${checklistId}/toggle`, 'PATCH', {});
    if (res.success) {
        const task = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}`, 'GET');
        renderChecklist(task.checklists || []);
    }
}

async function submitComment() {
    if (!currentTaskId) return;
    const input   = document.getElementById('comment-input');
    const content = input.value.trim();
    if (!content) return;

    const res = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}/comments`, 'POST', { content });
    if (res.success) {
        input.value = '';
        const task = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}`, 'GET');
        renderComments(task.comments || []);
        // Update comment count on card
        const card = document.getElementById(`task-${currentTaskId}`);
        if (card) {
            const countEl = card.querySelector('[data-comments]');
            if (countEl) countEl.textContent = parseInt(countEl.textContent || 0) + 1;
        }
    }
}

async function deleteTask() {
    if (!currentTaskId) return;
    if (!confirm('Delete this task? This cannot be undone.')) return;

    const res = await apiFetch(`${BASE_URL}/tasks/${currentTaskId}`, 'DELETE', {});
    if (res.success) {
        document.getElementById(`task-${currentTaskId}`)?.remove();
        closeTaskModal();
        updateColCounts();
    }
}

/* =====================================================
   ADD COLUMN
===================================================== */
function openAddColumnModal() {
    openModal('add-col-modal');
    document.getElementById('new-col-name').focus();
}

async function deleteColumn(listId, btn) {
    if (!confirm('Delete this column? It must be empty to proceed.')) return;

    const res = await apiFetch(`${BASE_URL}/lists/${listId}`, 'DELETE');
    if (res.success) {
        btn.closest('.kanban-col').remove();
    } else {
        alert(res.message ?? 'Cannot delete column.');
    }
}

async function submitNewColumn() {
    const name  = document.getElementById('new-col-name').value.trim();
    const color = document.getElementById('new-col-color').value;
    if (!name) return;

    const res = await apiFetch(`${BASE_URL}/lists`, 'POST', { name, color });
    if (res.success) {
        closeModal('add-col-modal');
        window.location.reload(); // Simplest way to show new column
    }
}

/* =====================================================
   API HELPER
===================================================== */
async function apiFetch(url, method = 'GET', body = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
    };
    if (body && method !== 'GET') {
        options.body = JSON.stringify(body);
    }

    try {
        const res  = await fetch(url, options);
        return await res.json();
    } catch (err) {
        console.error('API error:', err);
        return { success: false };
    }
}
</script>
@endpush