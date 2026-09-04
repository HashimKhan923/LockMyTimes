@extends('layouts.admin')
@section('title','Projects')
@section('page-title','Projects')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Total Projects','value'=>$stats['total'],  'icon'=>'folder',      'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Active',        'value'=>$stats['active'], 'icon'=>'play-circle', 'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Planning',      'value'=>$stats['planning'],'icon'=>'clock',      'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Overdue',       'value'=>$stats['overdue'],'icon'=>'alert-circle','bg'=>'bg-red-50',    'text'=>'text-red-600'],
    ] as $s)
    <div class="lmt-stat">
        <div>
            <p class="lmt-stat-label">{{ $s['label'] }}</p>
            <p class="lmt-stat-value text-2xl">{{ $s['value'] }}</p>
        </div>
        <div class="lmt-stat-icon {{ $s['bg'] }} {{ $s['text'] }}">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
    </div>
    @endforeach
</div>

{{-- Toolbar --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-2 flex-wrap">
        @foreach(['all'=>'All','planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold','completed'=>'Completed'] as $val=>$label)
        <a href="{{ route('admin.projects.index', $tenant) }}?status={{ $val }}"
           class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all
                  {{ $status === $val ? 'lmt-gradient-bg text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    <div class="flex items-center gap-2">
        <form action="{{ route('admin.projects.index', $tenant) }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}"/>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search projects…" class="lmt-input py-2 text-sm w-48"/>
            <input type="date" name="from" value="{{ request('from') }}" title="Due date from" class="lmt-input py-2 text-sm w-auto"/>
            <input type="date" name="to" value="{{ request('to') }}" title="Due date to" class="lmt-input py-2 text-sm w-auto"/>
            <button type="submit" class="lmt-btn-secondary lmt-btn-sm">Filter</button>
        </form>
        <button onclick="openModal('add-project-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Project
        </button>
    </div>
</div>

{{-- Project Cards Grid --}}
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($projects as $project)
    @php
    $statusColors = [
        'planning'  => 'lmt-badge-amber',
        'active'    => 'lmt-badge-green',
        'on_hold'   => 'lmt-badge-gray',
        'completed' => 'lmt-badge-brand',
        'cancelled' => 'lmt-badge-red',
        'archived'  => 'lmt-badge-gray',
    ];
    $priorityColors = [
        'urgent' => 'text-red-500',
        'high'   => 'text-amber-500',
        'normal' => 'text-blue-500',
        'low'    => 'text-gray-800',
    ];
    @endphp
    <div class="lmt-card p-0 overflow-hidden hover:shadow-md transition-shadow group">
        {{-- Color bar --}}
        <div class="h-1.5 w-full" style="background:{{ $project->color ?? '#6C7DF7' }}"></div>

        <div class="p-5">
            {{-- Header --}}
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <code class="text-xs text-gray-800 font-mono">{{ $project->code }}</code>
                        <span class="{{ $statusColors[$project->status] ?? 'lmt-badge-gray' }} text-xs">
                            {{ ucfirst(str_replace('_',' ',$project->status)) }}
                        </span>
                    </div>
                    <h3 class="font-black text-gray-900 truncate">{{ $project->name }}</h3>
                    @if($project->description)
                    <p class="text-xs text-gray-800 mt-1 line-clamp-2">{{ $project->description }}</p>
                    @endif
                </div>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-black ml-3 flex-shrink-0"
                     style="background:{{ $project->color ?? '#6C7DF7' }}">
                    {{ substr($project->name, 0, 1) }}
                </div>
            </div>

            {{-- Progress --}}
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-800">Progress</span>
                    <span class="font-bold text-gray-800">{{ $project->progress }}%</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                         style="width:{{ $project->progress }}%; background:{{ $project->color ?? '#6C7DF7' }}"></div>
                </div>
            </div>

            {{-- Meta --}}
            <div class="flex items-center justify-between text-xs text-gray-800 mb-4">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1">
                        <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                        {{ $project->completed_tasks_count }}/{{ $project->tasks_count }}
                    </span>
                    <span class="flex items-center gap-1">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        {{ $project->members_count }}
                    </span>
                    @if($project->due_date)
                    <span class="flex items-center gap-1 {{ $project->is_overdue ? 'text-red-500 font-semibold' : '' }}">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        {{ $project->due_date->format('M j') }}
                    </span>
                    @endif
                </div>
                <span class="{{ $priorityColors[$project->priority] ?? '' }} font-semibold capitalize">
                    {{ $project->priority }}
                </span>
            </div>

            {{-- Manager --}}
            @if($project->manager)
            <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                <div class="w-6 h-6 rounded-full lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold">
                    {{ substr($project->manager->first_name, 0, 1) }}
                </div>
                <span class="text-xs text-gray-800">{{ $project->manager->full_name }}</span>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="px-5 pb-4 flex items-center gap-2">
            <a href="{{ route('admin.projects.show', [$tenant, $project->id]) }}"
               class="flex-1 lmt-btn-secondary lmt-btn-sm text-center">
                <i data-lucide="layout-kanban" class="w-4 h-4"></i>
                Open Board
            </a>
            <button onclick="openEditProject({{ $project->id }}, {{ json_encode($project) }})"
                    class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-brand-50 hover:text-brand-600 flex items-center justify-center transition-colors">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
            </button>
            <form action="{{ route('admin.projects.destroy', [$tenant, $project->id]) }}"
                  method="POST" onsubmit="return confirm('Delete this project?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-16 md:col-span-3">
        <i data-lucide="folder-open" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
        <p class="font-black text-gray-800 text-lg">No projects yet</p>
        <p class="text-sm text-gray-800 mt-1 mb-5">Create your first project to get started</p>
        <button onclick="openModal('add-project-modal')" class="lmt-btn-primary lmt-btn-sm inline-flex">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Project
        </button>
    </div>
    @endforelse
</div>

@if($projects->hasPages())
<div class="mt-5">{{ $projects->links() }}</div>
@endif

{{-- Add Project Modal --}}
<div id="add-project-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-2xl">
        <h3 class="font-black text-gray-900 mb-5">Create New Project</h3>
        <form action="{{ route('admin.projects.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Project Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. Website Redesign 2026"/>
                </div>
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        @foreach(['internal'=>'Internal','client'=>'Client','product'=>'Product','campaign'=>'Campaign','maintenance'=>'Maintenance','other'=>'Other'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="lmt-select">
                        @foreach(['planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" required class="lmt-select">
                        @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                        <option value="{{ $v }}" {{ $v==='normal'?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Billing Type <span class="text-red-500">*</span></label>
                    <select name="billing_type" required class="lmt-select">
                        @foreach(['non_billable'=>'Non-Billable','hourly'=>'Hourly','fixed'=>'Fixed Price','milestone'=>'Milestone'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Project Manager</label>
                    <select name="project_manager_id" class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Department</label>
                    <select name="department_id" class="lmt-select">
                        <option value="">— None —</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Start Date</label>
                    <input type="date" name="start_date" class="lmt-input" value="{{ today()->toDateString() }}"/>
                </div>
                <div>
                    <label class="lmt-label">Due Date</label>
                    <input type="date" name="due_date" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Budget ($)</label>
                    <input type="number" name="budget_amount" step="100" min="0" class="lmt-input" placeholder="0"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Description</label>
                    <textarea name="description" class="lmt-textarea" rows="2" placeholder="Project overview…"></textarea>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create Project</button>
                <button type="button" onclick="closeModal('add-project-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Project Modal --}}
<div id="edit-project-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Edit Project</h3>
        <form id="edit-project-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Name</label>
                    <input type="text" name="name" id="edit-project-name" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Status</label>
                    <select name="status" id="edit-project-status" class="lmt-select">
                        @foreach(['planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold','completed'=>'Completed','cancelled'=>'Cancelled','archived'=>'Archived'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Priority</label>
                    <select name="priority" id="edit-project-priority" class="lmt-select">
                        @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Due Date</label>
                    <input type="date" name="due_date" id="edit-project-due" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" id="edit-project-color" class="lmt-input h-10 p-1"/>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save</button>
                <button type="button" onclick="closeModal('edit-project-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }
function openEditProject(id, project) {
    document.getElementById('edit-project-form').action = `/t/{{ $tenant }}/admin/projects/${id}`;
    document.getElementById('edit-project-name').value    = project.name;
    document.getElementById('edit-project-status').value  = project.status;
    document.getElementById('edit-project-priority').value= project.priority;
    document.getElementById('edit-project-due').value     = project.due_date ?? '';
    document.getElementById('edit-project-color').value   = project.color ?? '#6C7DF7';
    openModal('edit-project-modal');
}
['add-project-modal','edit-project-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush