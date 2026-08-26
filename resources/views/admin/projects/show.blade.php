@extends('layouts.admin')
@section('title', $project->name)
@section('page-title', $project->name)

@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <a href="{{ route('admin.projects.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        All Projects
    </a>
    <a href="{{ route('admin.projects.board', [$tenant, $project->id]) }}"
       class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="layout-kanban" class="w-4 h-4"></i>
        Open Kanban Board
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Header card --}}
        <div class="lmt-card p-0 overflow-hidden">
            <div class="h-2" style="background:{{ $project->color }}"></div>
            <div class="p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <code class="text-xs text-gray-800 font-mono">{{ $project->code }}</code>
                            @php
                            $sc = ['planning'=>'lmt-badge-amber','active'=>'lmt-badge-green','on_hold'=>'lmt-badge-gray','completed'=>'lmt-badge-brand','cancelled'=>'lmt-badge-red'];
                            @endphp
                            <span class="{{ $sc[$project->status] ?? 'lmt-badge-gray' }} text-xs capitalize">{{ str_replace('_',' ',$project->status) }}</span>
                            <span class="lmt-badge-gray text-xs capitalize">{{ $project->priority }} priority</span>
                        </div>
                        <h2 class="text-2xl font-black text-gray-900">{{ $project->name }}</h2>
                        @if($project->description)
                        <p class="text-gray-800 text-sm mt-2">{{ $project->description }}</p>
                        @endif
                    </div>
                </div>

                {{-- Progress --}}
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-800">Overall Progress</span>
                        <span class="font-black text-gray-900">{{ $project->progress }}%</span>
                    </div>
                    <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             style="width:{{ $project->progress }}%; background:{{ $project->color }}"></div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-4 gap-4">
                    @foreach([
                        ['Total Tasks',    $taskStats['total'],       'text-gray-900'],
                        ['Done',          $taskStats['done'],         'text-emerald-600'],
                        ['In Progress',   $taskStats['in_progress'],  'text-brand-600'],
                        ['Overdue',       $taskStats['overdue'],      'text-red-500'],
                    ] as [$label,$val,$color])
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black {{ $color }}">{{ $val }}</p>
                        <p class="text-xs text-gray-800 mt-0.5">{{ $label }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Task Lists Summary --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-4">Board Columns</h3>
            <div class="space-y-3">
                @foreach($project->taskLists as $list)
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full flex-shrink-0" style="background:{{ $list->color }}"></div>
                    <span class="text-sm font-semibold text-gray-800 w-32">{{ $list->name }}</span>
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                        @php $count = $list->tasks->count(); $maxCount = max($project->taskLists->max(fn($l)=>$l->tasks->count()), 1); @endphp
                        <div class="h-full rounded-full" style="width:{{ round(($count/$maxCount)*100) }}%; background:{{ $list->color }}"></div>
                    </div>
                    <span class="text-sm font-bold text-gray-800 w-8 text-right">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Milestones --}}
        @if($project->milestones->isNotEmpty())
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-4">Milestones</h3>
            <div class="space-y-3">
                @foreach($project->milestones as $ms)
                @php
                $msColors = ['upcoming'=>'lmt-badge-amber','in_progress'=>'lmt-badge-brand','completed'=>'lmt-badge-green','overdue'=>'lmt-badge-red','cancelled'=>'lmt-badge-gray'];
                @endphp
                <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-none">
                    <div class="w-8 h-8 rounded-lg lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ $ms->progress }}%
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 text-sm">{{ $ms->title }}</p>
                        <p class="text-xs text-gray-800">Due: {{ $ms->due_date->format('M j, Y') }}</p>
                    </div>
                    <span class="{{ $msColors[$ms->status] ?? 'lmt-badge-gray' }} text-xs capitalize">{{ $ms->status }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Right --}}
    <div class="space-y-5">

        {{-- Project Details --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-4">Details</h3>
            <div class="space-y-3">
                @foreach([
                    ['Type',       ucfirst($project->type)],
                    ['Billing',    ucfirst(str_replace('_',' ',$project->billing_type))],
                    ['Start Date', $project->start_date?->format('M j, Y') ?? '—'],
                    ['Due Date',   $project->due_date?->format('M j, Y') ?? '—'],
                    ['Budget',     $project->budget_amount > 0 ? '$'.number_format($project->budget_amount,0) : '—'],
                ] as [$k,$v])
                <div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-none">
                    <span class="text-xs text-gray-800">{{ $k }}</span>
                    <span class="text-xs font-semibold text-gray-800">{{ $v }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Members --}}
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-black text-gray-900">Team</h3>
                <button onclick="openModal('add-member-modal')"
                        class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($project->members as $member)
                <div class="flex items-center gap-3 p-3">
                    <div class="lmt-avatar-sm font-bold text-xs">{{ substr($member->employee->first_name??'?',0,1) }}</div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 text-sm">{{ $member->employee->full_name }}</p>
                        <p class="text-xs text-gray-800 capitalize">{{ $member->role }}</p>
                    </div>
                    @if($member->role !== 'owner')
                    <form action="{{ route('admin.projects.members.remove', [$tenant, $project->id, $member->id]) }}"
                          method="POST" onsubmit="return confirm('Remove member?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-6 h-6 rounded text-gray-800 hover:text-red-500 flex items-center justify-center">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Add Member Modal --}}
<div id="add-member-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Team Member</h3>
        <form action="{{ route('admin.projects.members.add', [$tenant, $project->id]) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Role</label>
                <select name="role" class="lmt-select">
                    @foreach(['member'=>'Member','manager'=>'Manager','viewer'=>'Viewer'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="can_manage_tasks" value="1" class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-800">Can Manage Tasks</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Add Member</button>
                <button type="button" onclick="closeModal('add-member-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }
document.getElementById('add-member-modal')?.addEventListener('click', function(e) { if(e.target===this) closeModal('add-member-modal'); });
</script>
@endpush