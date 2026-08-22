@extends('layouts.employee')
@section('title', $project->name . ' — Board')
@section('page-title', $project->name)

@push('head')
<style>
/* =====================================================
   KANBAN BOARD STYLES  (mirrors admin board exactly)
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
    transition: all .15s ease;
    position: relative;
}
.task-card.can-drag {
    cursor: grab;
}
.task-card.can-drag:hover {
    border-color: #6C7DF7;
    box-shadow: 0 4px 12px rgba(108,125,247,.12);
    transform: translateY(-1px);
}
.task-card.no-drag {
    cursor: pointer;
    opacity: .85;
}
.task-card.no-drag:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
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

/* Priority */
.priority-urgent { border-left: 3px solid #EF4444; }
.priority-high    { border-left: 3px solid #F59E0B; }
.priority-normal  { border-left: 3px solid #6C7DF7; }
.priority-low     { border-left: 3px solid #94A3B8; }

/* Scrollbars */
.kanban-col-body::-webkit-scrollbar { width: 4px; }
.kanban-col-body::-webkit-scrollbar-track { background: transparent; }
.kanban-col-body::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
.kanban-wrapper::-webkit-scrollbar { height: 6px; }
.kanban-wrapper::-webkit-scrollbar-track { background: transparent; }
.kanban-wrapper::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }

/* Type badges */
.badge-bug         { background:#FEE2E2; color:#DC2626; }
.badge-feature     { background:#D1FAE5; color:#059669; }
.badge-epic        { background:#EDE9FE; color:#7C3AED; }
.badge-story       { background:#FEF3C7; color:#D97706; }
.badge-improvement { background:#DBEAFE; color:#2563EB; }
.badge-support     { background:#F0FDF4; color:#16A34A; }
.badge-task        { background:#F1F5F9; color:#475569; }

/* "YOU" badge */
.badge-you { background:#EEF2FF; color:#4F46E5; font-size:9px; font-weight:900; padding:1px 5px; border-radius:999px; }

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
#task-modal-backdrop.hidden { display: none; }
#task-modal {
    background: #fff;
    border-radius: 1.25rem;
    width: 100%;
    max-width: 680px;
    box-shadow: 0 25px 60px rgba(0,0,0,.2);
    overflow: hidden;
    position: relative;
}
</style>
@endpush

@section('content')
{{-- Remove default page padding so board uses full width --}}
@php $fullWidth = true; @endphp
@php
    $myTaskIdSet = $myTaskIds->toArray();
@endphp

{{-- ===== PROJECT HEADER ===== --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('employee.projects.index', $tenantSlug) }}"
           class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center text-gray-800 hover:bg-gray-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-lg"
             style="background:{{ $project->color }}">
            {{ substr($project->name, 0, 1) }}
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="font-black text-gray-900 text-lg">{{ $project->name }}</h1>
                <span class="lmt-badge-gray text-xs capitalize">{{ str_replace('_',' ',$project->status) }}</span>
                @if($myMember)
                <span class="lmt-badge-indigo text-xs uppercase">{{ str_replace('_',' ',$myMember->role) }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-800">{{ $project->code }}
                @if($project->manager) · PM: {{ $project->manager->full_name }} @endif
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        {{-- Stats --}}
        <div class="bg-gray-100 rounded-xl px-3 py-1.5 text-center">
            <p class="text-sm font-black text-emerald-600">{{ $taskStats['done'] }}</p>
            <p class="text-[10px] text-gray-800">done</p>
        </div>
        <div class="bg-gray-100 rounded-xl px-3 py-1.5 text-center">
            <p class="text-sm font-black text-brand-600">{{ $taskStats['in_progress'] }}</p>
            <p class="text-[10px] text-gray-800">in progress</p>
        </div>
        @if($taskStats['overdue'] > 0)
        <div class="bg-gray-100 rounded-xl px-3 py-1.5 text-center">
            <p class="text-sm font-black text-red-500">{{ $taskStats['overdue'] }}</p>
            <p class="text-[10px] text-gray-800">overdue</p>
        </div>
        @endif

        {{-- Member avatars --}}
        <div class="flex -space-x-2">
            @foreach($members->take(5) as $m)
            @if($m->employee)
            <div class="w-8 h-8 rounded-full border-2 border-white lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold"
                 title="{{ $m->employee->full_name }}">
                {{ substr($m->employee->first_name ?? '?', 0, 1) }}
            </div>
            @endif
            @endforeach
        </div>

        @if($isManager)
        <button onclick="document.getElementById('add-task-modal').classList.remove('hidden');document.getElementById('add-task-modal').classList.add('flex');"
                class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Task
        </button>
        @endif
    </div>
</div>

{{-- ===== KANBAN BOARD ===== --}}
<div class="kanban-wrapper" id="kanban-board">

    @foreach($taskLists as $list)
    <div class="kanban-col" data-list-id="{{ $list->id }}">

        {{-- Column header --}}
        <div class="kanban-col-header">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" style="background:{{ $list->color ?? '#6C7DF7' }}"></div>
                <span class="font-bold text-gray-800 text-sm">{{ $list->name }}</span>
                <span class="w-5 h-5 rounded-full bg-gray-200 text-gray-600 text-xs font-bold flex items-center justify-center">
                    {{ $list->tasks->count() }}
                </span>
            </div>
            @if($isManager)
            <button onclick="quickAddTask({{ $list->id }})"
                    class="w-6 h-6 rounded-lg text-gray-800 hover:text-brand-600 hover:bg-brand-50 flex items-center justify-center transition-colors"
                    title="Quick Add">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            </button>
            @endif
        </div>

        {{-- Drop zone --}}
        <div class="kanban-col-body"
             id="col-{{ $list->id }}"
             data-list-id="{{ $list->id }}"
             ondragover="onDragOver(event)"
             ondrop="onDrop(event)"
             ondragleave="onDragLeave(event)">

            @foreach($list->tasks as $task)
            @php
                $canDrag = isset($myTaskIdSet[$task->id]) || $isManager;
            @endphp
            <div class="task-card priority-{{ $task->priority }} {{ $canDrag ? 'can-drag' : 'no-drag' }}"
                 id="task-{{ $task->id }}"
                 data-task-id="{{ $task->id }}"
                 data-list-id="{{ $list->id }}"
                 data-can-drag="{{ $canDrag ? '1' : '0' }}"
                 draggable="{{ $canDrag ? 'true' : 'false' }}"
                 @if($canDrag)
                 ondragstart="onDragStart(event)"
                 ondragend="onDragEnd(event)"
                 ondragover="event.preventDefault()"
                 ondrop="onDrop(event)"
                 @endif
                 onclick="openTaskModal({{ $task->id }})">

                {{-- Type + date row --}}
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="badge-{{ $task->type }} text-[10px] font-bold px-2 py-0.5 rounded-full capitalize">
                            {{ $task->type }}
                        </span>
                        @if(isset($myTaskIdSet[$task->id]))
                        <span class="badge-you">YOU</span>
                        @endif
                    </div>
                    @if($task->due_date)
                    <span class="text-[10px] flex items-center gap-0.5 {{ $task->due_date->isPast() && !in_array($task->status,['done','cancelled']) ? 'text-red-500 font-bold' : 'text-gray-800' }}">
                        <i data-lucide="calendar" class="w-2.5 h-2.5"></i>
                        {{ $task->due_date->format('M j') }}
                    </span>
                    @endif
                </div>

                {{-- Title --}}
                <p class="text-sm font-semibold text-gray-900 leading-snug mb-2 line-clamp-2">
                    {{ $task->title }}
                </p>

                {{-- Progress bar --}}
                @if($task->progress > 0)
                <div class="h-1 bg-gray-100 rounded-full overflow-hidden mb-2">
                    <div class="h-full rounded-full" style="width:{{ $task->progress }}%; background:{{ $list->color ?? '#6C7DF7' }}"></div>
                </div>
                @endif

                {{-- Footer --}}
                <div class="flex items-center justify-between mt-2">
                    <div class="flex -space-x-1.5 card-assignees">
                        @foreach($task->assignees->take(3) as $assignee)
                        @if($assignee->employee)
                        <div class="w-6 h-6 rounded-full border-2 border-white lmt-gradient-bg flex items-center justify-center text-white text-[9px] font-bold"
                             title="{{ $assignee->employee->full_name }}">
                            {{ substr($assignee->employee->first_name ?? '?', 0, 1) }}
                        </div>
                        @endif
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2 text-gray-800">
                        @if(($task->comments_count ?? 0) > 0)
                        <span class="flex items-center gap-0.5 text-[10px]">
                            <i data-lucide="message-circle" class="w-3 h-3"></i>
                            {{ $task->comments_count }}
                        </span>
                        @endif
                        @if(($task->subtasks_count ?? 0) > 0)
                        <span class="flex items-center gap-0.5 text-[10px] {{ ($task->completed_subtasks_count ?? 0) >= $task->subtasks_count ? 'text-emerald-500' : '' }}">
                            <i data-lucide="check-square" class="w-3 h-3"></i>
                            {{ $task->completed_subtasks_count ?? 0 }}/{{ $task->subtasks_count }}
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

            {{-- Quick add inline (managers only) --}}
            @if($isManager)
            <div id="quick-add-{{ $list->id }}" class="hidden">
                <div class="bg-white rounded-xl border-2 border-brand-300 p-3 mt-1">
                    <textarea id="quick-title-{{ $list->id }}"
                              class="w-full text-sm text-gray-900 resize-none border-none outline-none bg-transparent"
                              rows="2" placeholder="Task title…"></textarea>
                    <div class="flex gap-2 mt-2">
                        <button onclick="submitQuickAdd({{ $list->id }})" class="lmt-btn-primary lmt-btn-sm">Add</button>
                        <button onclick="cancelQuickAdd({{ $list->id }})" class="lmt-btn-secondary lmt-btn-sm">Cancel</button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach

</div>

{{-- =====================================================
     TASK DETAIL MODAL
===================================================== --}}
<div id="task-modal-backdrop" class="hidden" onclick="if(event.target===this) closeTaskModal()">
    <div id="task-modal">
        <div id="task-modal-header" class="h-1.5 w-full"></div>
        <div class="p-6">

            {{-- Loading --}}
            <div id="task-modal-loading" class="text-center py-10">
                <div class="w-8 h-8 border-4 border-brand-200 border-t-brand-600 rounded-full animate-spin mx-auto"></div>
                <p class="text-sm text-gray-800 mt-3">Loading task…</p>
            </div>

            {{-- Content --}}
            <div id="task-modal-content" class="hidden">

                {{-- Type + close --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span id="tmd-type" class="text-xs font-bold px-2 py-1 rounded-full"></span>
                        <span id="tmd-code" class="text-xs text-gray-800 font-mono"></span>
                        <span id="tmd-you-badge" class="badge-you hidden">YOUR TASK</span>
                    </div>
                    <button onclick="closeTaskModal()"
                            class="w-8 h-8 rounded-lg text-gray-800 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                {{-- Title --}}
                <input id="tmd-title" type="text"
                       class="w-full text-xl font-black text-gray-900 mb-3 leading-tight border-0 border-b-2 border-transparent focus:border-brand-400 focus:outline-none bg-transparent py-1 transition-colors"
                       placeholder="Task title…"/>

                <div class="grid grid-cols-3 gap-5">
                    {{-- Left — content --}}
                    <div class="col-span-2 space-y-5">

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
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-bold text-gray-800 uppercase tracking-wider">Checklist</p>
                                <span id="tmd-checklist-progress" class="text-xs font-bold text-gray-800"></span>
                            </div>
                            <div id="tmd-checklist" class="space-y-1.5 mb-3"></div>
                            <div id="tmd-checklist-add" class="flex gap-2">
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

                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Status</p>
                            <select id="tmd-status" onchange="updateTaskField('status', this.value)"
                                    class="lmt-select py-2 text-sm w-full">
                                @foreach(['backlog'=>'Backlog','todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','on_hold'=>'On Hold','done'=>'Done','cancelled'=>'Cancelled'] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Priority</p>
                            <select id="tmd-priority" onchange="updateTaskField('priority', this.value)"
                                    class="lmt-select py-2 text-sm w-full">
                                @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-2">Assignees</p>
                            <div id="tmd-assignees" class="flex flex-wrap gap-1.5"></div>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Due Date</p>
                            <input type="date" id="tmd-due" class="lmt-input py-1.5 text-sm w-full"/>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Estimated Hours</p>
                            <input type="number" id="tmd-hours" min="0" step="0.5"
                                   class="lmt-input py-1.5 text-sm w-full" placeholder="0"/>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-gray-800 mb-1">Progress</p>
                            <div class="flex items-center gap-2">
                                <input type="range" id="tmd-progress" min="0" max="100" step="5"
                                       class="flex-1 h-2 accent-brand-600"
                                       oninput="document.getElementById('tmd-progress-val').textContent=this.value+'%'"/>
                                <span id="tmd-progress-val" class="text-xs font-bold text-gray-600 w-8">0%</span>
                            </div>
                        </div>

                        <button id="tmd-save-btn" onclick="saveTaskEdits()"
                                class="w-full lmt-btn-primary lmt-btn-sm">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =====================================================
     ADD TASK MODAL (managers only)
===================================================== --}}
@if($isManager)
<div id="add-task-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">New Task</h3>
        <div class="space-y-4">
            <div>
                <label class="lmt-label">Column <span class="text-red-500">*</span></label>
                <select id="task-list-select" class="lmt-select">
                    @foreach($taskLists as $list)
                    <option value="{{ $list->id }}">{{ $list->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Title <span class="text-red-500">*</span></label>
                <input type="text" id="task-title" class="lmt-input" placeholder="What needs to be done?"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Type</label>
                    <select id="task-type" class="lmt-select">
                        @foreach(['task'=>'Task','bug'=>'Bug','feature'=>'Feature','epic'=>'Epic','story'=>'Story','improvement'=>'Improvement','support'=>'Support'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Priority</label>
                    <select id="task-priority" class="lmt-select">
                        @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                        <option value="{{ $v }}" {{ $v==='normal'?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Due Date</label>
                    <input type="date" id="task-due" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Estimated (hours)</label>
                    <input type="number" id="task-hours" step="0.5" min="0" class="lmt-input" placeholder="0"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Assign To</label>
                <div class="rounded-xl border border-gray-200 max-h-36 overflow-y-auto divide-y divide-gray-50">
                    @foreach($members as $m)
                    @if($m->employee)
                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" class="new-task-assignee-cb w-3.5 h-3.5 rounded" value="{{ $m->employee_id }}"/>
                        <div class="w-6 h-6 rounded-full bg-brand-100 text-brand-700 text-[10px] font-black flex items-center justify-center flex-shrink-0">
                            {{ substr($m->employee->first_name, 0, 1) }}
                        </div>
                        <span class="text-sm text-gray-700">{{ $m->employee->full_name }}</span>
                        <span class="ml-auto text-[10px] font-bold text-gray-800 uppercase">{{ $m->role }}</span>
                    </label>
                    @endif
                    @endforeach
                </div>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea id="task-desc" class="lmt-textarea" rows="2" placeholder="Optional details…"></textarea>
            </div>
            <div class="flex gap-3">
                <button onclick="submitNewTask()" class="lmt-btn-primary flex-1">Create Task</button>
                <button onclick="closeModal('add-task-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const TENANT     = @json($tenantSlug);
const PROJECT_ID = {{ $project->id }};
const MY_EMP_ID  = {{ $emp->id }};
const IS_MGR     = {{ $isManager ? 'true' : 'false' }};
const MY_TASKS   = new Set({{ Js::from(array_keys($myTaskIdSet)) }});
const BASE_URL   = `/t/${TENANT}/portal/projects/${PROJECT_ID}`;
const TASK_BASE  = `/t/${TENANT}/portal/tasks`;
const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let dragTask      = null;
let dragSource    = null;
let currentTaskId = null;
let currentTaskMine = false;

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
    const afterEl = getDragAfterElement(col, e.clientY);
    document.querySelectorAll('.drag-placeholder').forEach(el => el.remove());
    const ph = document.createElement('div');
    ph.className = 'task-card drag-placeholder';
    afterEl ? col.insertBefore(ph, afterEl) : col.appendChild(ph);
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
    const col = e.currentTarget.classList.contains('kanban-col-body')
        ? e.currentTarget
        : e.currentTarget.closest('.kanban-col-body');
    if (!col) return;

    const newListId = col.dataset.listId;
    const taskId    = e.dataTransfer.getData('text/plain');
    const taskEl    = document.getElementById(`task-${taskId}`);

    col.classList.remove('drag-over');
    document.querySelectorAll('.drag-placeholder').forEach(el => el.remove());
    if (!taskEl) return;

    const afterEl = getDragAfterElement(col, e.clientY);
    afterEl ? col.insertBefore(taskEl, afterEl) : col.appendChild(taskEl);
    taskEl.dataset.listId = newListId;

    const orderedIds = [...col.querySelectorAll('.task-card[data-task-id]')].map(el => el.dataset.taskId);

    apiFetch(`${BASE_URL}/tasks/${taskId}/move`, 'PATCH', {
        task_list_id: newListId,
        sort_order:   orderedIds.indexOf(taskId),
        ordered_ids:  orderedIds,
    }).then(data => {
        if (data.success) {
            updateColCounts();
            if (window.lucide) lucide.createIcons();
        }
    });
}

function getDragAfterElement(container, y) {
    const els = [...container.querySelectorAll('.task-card:not(.dragging):not(.drag-placeholder)')];
    return els.reduce((closest, child) => {
        const box    = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > (closest.offset ?? -Infinity)) return { offset, element: child };
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
   QUICK ADD (managers)
===================================================== */
function quickAddTask(listId) {
    document.querySelectorAll('[id^="quick-add-"]').forEach(el => el.classList.add('hidden'));
    const el = document.getElementById(`quick-add-${listId}`);
    if (el) { el.classList.remove('hidden'); document.getElementById(`quick-title-${listId}`).focus(); }
}
function cancelQuickAdd(listId) {
    document.getElementById(`quick-add-${listId}`)?.classList.add('hidden');
}
async function submitQuickAdd(listId) {
    const title = document.getElementById(`quick-title-${listId}`).value.trim();
    if (!title) return;
    const res = await apiFetch(`${BASE_URL}/tasks`, 'POST', { task_list_id: listId, title, type: 'task', priority: 'normal' });
    if (res.success) {
        addTaskCardToDOM(res.task, listId);
        document.getElementById(`quick-title-${listId}`).value = '';
        cancelQuickAdd(listId);
        updateColCounts();
        if (window.lucide) lucide.createIcons();
    }
}

/* =====================================================
   ADD TASK MODAL (managers)
===================================================== */
function openModal(id) {
    document.getElementById(id)?.classList.remove('hidden');
    document.getElementById(id)?.classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id)?.classList.add('hidden');
    document.getElementById(id)?.classList.remove('flex');
}
async function submitNewTask() {
    const title = document.getElementById('task-title').value.trim();
    if (!title) return;
    const assigneeIds = [...document.querySelectorAll('.new-task-assignee-cb:checked')].map(cb => cb.value);
    const res = await apiFetch(`${BASE_URL}/tasks`, 'POST', {
        task_list_id:    document.getElementById('task-list-select').value,
        title,
        type:            document.getElementById('task-type').value,
        priority:        document.getElementById('task-priority').value,
        due_date:        document.getElementById('task-due').value || null,
        estimated_hours: document.getElementById('task-hours').value || 0,
        description:     document.getElementById('task-desc').value || null,
        assignee_ids:    assigneeIds,
    });
    if (res.success) {
        addTaskCardToDOM(res.task, res.task.task_list_id);
        closeModal('add-task-modal');
        document.getElementById('task-title').value = '';
        document.getElementById('task-desc').value  = '';
        document.querySelectorAll('.new-task-assignee-cb').forEach(cb => cb.checked = false);
        updateColCounts();
        if (window.lucide) lucide.createIcons();
    }
}

function addTaskCardToDOM(task, listId) {
    const col  = document.getElementById(`col-${listId}`);
    if (!col) return;
    const mine = MY_TASKS.has(task.id) || IS_MGR;
    const card = document.createElement('div');
    card.className      = `task-card priority-${task.priority} ${mine ? 'can-drag' : 'no-drag'}`;
    card.id             = `task-${task.id}`;
    card.dataset.taskId = task.id;
    card.dataset.listId = listId;
    card.draggable      = mine;
    card.onclick        = () => openTaskModal(task.id);
    if (mine) {
        card.addEventListener('dragstart', onDragStart);
        card.addEventListener('dragend',   onDragEnd);
        card.addEventListener('dragover',  e => e.preventDefault());
        card.addEventListener('drop',      onDrop);
    }
    const assigneeHtml = (task.assignees || []).slice(0, 3).map(a =>
        `<div class="w-6 h-6 rounded-full border-2 border-white lmt-gradient-bg flex items-center justify-center text-white text-[9px] font-bold" title="${a.employee?.full_name ?? ''}">${(a.employee?.first_name ?? '?')[0]}</div>`
    ).join('');
    card.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-1.5">
                <span class="badge-${task.type} text-[10px] font-bold px-2 py-0.5 rounded-full capitalize">${task.type}</span>
                ${MY_TASKS.has(task.id) ? '<span class="badge-you">YOU</span>' : ''}
            </div>
        </div>
        <p class="text-sm font-semibold text-gray-900 leading-snug mb-2 line-clamp-2">${task.title}</p>
        <div class="flex items-center justify-between mt-2">
            <div class="flex -space-x-1.5 card-assignees">${assigneeHtml}</div>
            <div class="flex items-center gap-2 text-gray-800"></div>
        </div>`;
    const qa = document.getElementById(`quick-add-${listId}`);
    qa ? col.insertBefore(card, qa) : col.appendChild(card);
}

/* =====================================================
   TASK DETAIL MODAL
===================================================== */
async function openTaskModal(taskId) {
    currentTaskId = taskId;
    document.getElementById('task-modal-backdrop').classList.remove('hidden');
    document.getElementById('task-modal-loading').classList.remove('hidden');
    document.getElementById('task-modal-content').classList.add('hidden');

    const task = await apiFetch(`${TASK_BASE}/${taskId}`, 'GET');
    if (task && !task.error) renderTaskModal(task);
}

function closeTaskModal() {
    document.getElementById('task-modal-backdrop').classList.add('hidden');
    currentTaskId  = null;
    currentTaskMine = false;
}

function renderTaskModal(task) {
    currentTaskMine = MY_TASKS.has(task.id) || IS_MGR;

    const priorityColors = { urgent:'#EF4444', high:'#F59E0B', normal:'#6C7DF7', low:'#94A3B8' };
    document.getElementById('task-modal-header').style.background = priorityColors[task.priority] ?? '#6C7DF7';

    document.getElementById('tmd-code').textContent  = task.task_code ?? '';
    document.getElementById('tmd-title').value       = task.title ?? '';
    document.getElementById('tmd-status').value      = task.status ?? 'todo';
    document.getElementById('tmd-priority').value    = task.priority ?? 'normal';
    document.getElementById('tmd-description').value = task.description ?? '';
    document.getElementById('tmd-hours').value       = task.estimated_hours > 0 ? task.estimated_hours : '';
    document.getElementById('tmd-due').value         = task.due_date ? task.due_date.substring(0, 10) : '';
    const prog = task.progress ?? 0;
    document.getElementById('tmd-progress').value    = prog;
    document.getElementById('tmd-progress-val').textContent = prog + '%';

    // Type badge
    const typeBadge = document.getElementById('tmd-type');
    typeBadge.className   = `badge-${task.type} text-xs font-bold px-2 py-1 rounded-full capitalize`;
    typeBadge.textContent = task.type;

    // YOU badge
    const youBadge = document.getElementById('tmd-you-badge');
    youBadge.classList.toggle('hidden', !MY_TASKS.has(task.id));

    // Lock fields based on role & ownership
    const readOnly = !currentTaskMine; // non-mine tasks fully read-only
    const mgrReserved = !IS_MGR;                    // only managers can change these

    // Title & description — editable on own tasks
    ['tmd-title','tmd-description'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.readOnly = readOnly;
    });

    // Status — editable on own tasks, not others
    const statusEl = document.getElementById('tmd-status');
    if (statusEl) statusEl.disabled = readOnly;

    // Priority, due date, estimated hours — manager-only always
    const tmdPriority = document.getElementById('tmd-priority');
    if (tmdPriority) { tmdPriority.disabled = mgrReserved; tmdPriority.style.opacity = mgrReserved ? '.6' : '1'; tmdPriority.title = mgrReserved ? 'Only managers can change priority' : ''; }

    const tmdDue = document.getElementById('tmd-due');
    if (tmdDue) { tmdDue.readOnly = mgrReserved; tmdDue.style.opacity = mgrReserved ? '.6' : '1'; tmdDue.title = mgrReserved ? 'Only managers can change due date' : ''; }

    const tmdHours = document.getElementById('tmd-hours');
    if (tmdHours) { tmdHours.readOnly = mgrReserved; tmdHours.style.opacity = mgrReserved ? '.6' : '1'; tmdHours.title = mgrReserved ? 'Only managers can change estimated hours' : ''; }

    // Progress — editable on own tasks
    document.getElementById('tmd-progress').disabled = readOnly;

    // Save button — only show for own tasks (saves title, description, progress, status)
    const saveBtn = document.getElementById('tmd-save-btn');
    if (saveBtn) saveBtn.style.display = readOnly ? 'none' : '';

    // Checklist add — only on own tasks
    const clAdd = document.getElementById('tmd-checklist-add');
    if (clAdd) clAdd.style.display = readOnly ? 'none' : '';

    // Assignees display
    renderAssigneesList(task.assignees || []);

    // Attachments
    renderAttachments(task.attachments || []);

    // Checklist
    renderChecklist(task.checklists || []);

    // Comments
    renderComments(task.comments || []);

    document.getElementById('task-modal-loading').classList.add('hidden');
    document.getElementById('task-modal-content').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function renderAttachments(attachments) {
    const container = document.getElementById('tmd-attachments');
    if (!container) return;
    if (!attachments || !attachments.length) {
        container.innerHTML = '<p class="text-xs text-gray-800 italic">No attachments yet.</p>';
        return;
    }
    container.innerHTML = attachments.map(a => {
        const isImage = (a.mime_type ?? '').startsWith('image/');
        const icon    = isImage ? 'image' : 'paperclip';
        const sizeHuman = a.file_size_human ?? (a.file_size ? (a.file_size / 1024).toFixed(1) + ' KB' : '');
        return `
        <div class="flex items-center gap-2 text-sm text-gray-700 bg-gray-50 rounded-lg px-3 py-2">
            <i data-lucide="${icon}" class="w-3.5 h-3.5 text-gray-800 flex-shrink-0"></i>
            <a href="${TASK_BASE}/${currentTaskId}/attachments/${a.id}" target="_blank"
               class="flex-1 truncate hover:text-brand-600 font-medium text-xs">${a.file_name}</a>
            <span class="text-[10px] text-gray-800 flex-shrink-0">${sizeHuman}</span>
            <button onclick="deleteAttachment(${a.id}, this)"
                    class="text-gray-800 hover:text-red-500 flex-shrink-0 transition-colors">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>`;
    }).join('');
    if (window.lucide) lucide.createIcons();
}

async function uploadAttachments(input) {
    if (!currentTaskId || !input.files.length) return;
    const formData = new FormData();
    for (const file of input.files) formData.append('files[]', file);
    formData.append('_token', CSRF);
    try {
        const res = await fetch(`${TASK_BASE}/${currentTaskId}/attachments`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        });
        const data = await res.json();
        if (data.success) {
            // Reload full task to get updated attachments list
            const task = await apiFetch(`${TASK_BASE}/${currentTaskId}`, 'GET');
            renderAttachments(task.attachments || []);
        }
    } catch (err) { console.error(err); }
    input.value = '';
}

async function deleteAttachment(attachmentId, btn) {
    const res = await apiFetch(`${TASK_BASE}/${currentTaskId}/attachments/${attachmentId}`, 'DELETE');
    if (res.success) btn.closest('div').remove();
}

function renderAssigneesList(assignees) {
    const el = document.getElementById('tmd-assignees');
    el.innerHTML = assignees.length === 0
        ? '<span class="text-xs text-gray-800 italic">No assignees</span>'
        : assignees.map(a => `
            <div class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 text-xs font-bold">
                <div class="w-4 h-4 rounded-full bg-brand-200 text-brand-800 text-[9px] font-black flex items-center justify-center">
                    ${(a.employee?.first_name ?? '?')[0]}
                </div>
                ${a.employee?.first_name ?? 'User'}
            </div>`).join('');
}

function renderChecklist(items) {
    const container = document.getElementById('tmd-checklist');
    const progress  = document.getElementById('tmd-checklist-progress');
    container.innerHTML = '';
    if (!items.length) { progress.textContent = ''; return; }
    const done = items.filter(i => i.is_completed).length;
    progress.textContent = `${done}/${items.length}`;
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 group';
        div.innerHTML = `
            <input type="checkbox" ${item.is_completed ? 'checked' : ''}
                   ${!currentTaskMine ? 'disabled' : ''}
                   onchange="toggleChecklist(${item.id}, this.checked)"
                   class="w-4 h-4 rounded cursor-pointer"/>
            <span class="text-sm text-gray-700 flex-1 ${item.is_completed ? 'line-through text-gray-800' : ''}">${item.item}</span>`;
        container.appendChild(div);
    });
}

function renderComments(comments) {
    const container = document.getElementById('tmd-comments');
    container.innerHTML = '';
    if (!comments.length) {
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
                    <span class="text-xs font-semibold text-gray-700">${c.user?.name ?? 'User'}</span>
                    <span class="text-[10px] text-gray-800">${new Date(c.created_at).toLocaleDateString()}</span>
                </div>
                <p class="text-sm text-gray-600">${c.content}</p>
            </div>`;
        container.appendChild(div);
    });
}

/* =====================================================
   TASK ACTIONS
===================================================== */
async function saveTaskEdits() {
    if (!currentTaskId || !currentTaskMine) return;
    const payload = {
        title:       document.getElementById('tmd-title').value.trim(),
        description: document.getElementById('tmd-description').value.trim() || null,
        progress:    parseInt(document.getElementById('tmd-progress').value, 10),
        status:      document.getElementById('tmd-status').value,
    };
    const res = await apiFetch(`${TASK_BASE}/${currentTaskId}`, 'PUT', payload);
    if (res.success) {
        const card = document.getElementById(`task-${currentTaskId}`);
        if (card) {
            const titleEl = card.querySelector('p.text-sm.font-semibold');
            if (titleEl) titleEl.textContent = payload.title;
        }
        const btn = document.getElementById('tmd-save-btn');
        if (btn) { btn.textContent = ' Saved'; setTimeout(() => { btn.innerHTML = '<i data-lucide="save" class="w-3.5 h-3.5 inline-block mr-1"></i>Save Changes'; if(window.lucide) lucide.createIcons(); }, 1500); }
    }
}

async function updateTaskField(field, value) {
    if (!currentTaskId || !currentTaskMine) return;
    await apiFetch(`${TASK_BASE}/${currentTaskId}`, 'PUT', { [field]: value });
}

async function addChecklistItem() {
    if (!currentTaskId || !currentTaskMine) return;
    const input = document.getElementById('checklist-input');
    const item  = input.value.trim();
    if (!item) return;
    const res = await apiFetch(`${TASK_BASE}/${currentTaskId}/checklists`, 'POST', { item });
    if (res.success || res.checklist) {
        input.value = '';
        const task = await apiFetch(`${TASK_BASE}/${currentTaskId}`, 'GET');
        renderChecklist(task.checklists || []);
    }
}

async function toggleChecklist(checklistId, checked) {
    if (!currentTaskId || !currentTaskMine) return;
    await apiFetch(`${TASK_BASE}/${currentTaskId}/checklists/${checklistId}`, 'PATCH', {});
    const task = await apiFetch(`${TASK_BASE}/${currentTaskId}`, 'GET');
    renderChecklist(task.checklists || []);
}

async function submitComment() {
    if (!currentTaskId) return;
    const input   = document.getElementById('comment-input');
    const content = input.value.trim();
    if (!content) return;
    const res = await apiFetch(`${TASK_BASE}/${currentTaskId}/comments`, 'POST', { content });
    if (res.success) {
        input.value = '';
        const task = await apiFetch(`${TASK_BASE}/${currentTaskId}`, 'GET');
        renderComments(task.comments || []);
    }
}

/* =====================================================
   API HELPER
===================================================== */
async function apiFetch(url, method = 'GET', body = null) {
    const options = {
        method,
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };
    if (body && method !== 'GET') options.body = JSON.stringify(body);
    try {
        const res = await fetch(url, options);
        return await res.json();
    } catch (err) {
        console.error('API error:', err);
        return { success: false };
    }
}
</script>
@endpush
