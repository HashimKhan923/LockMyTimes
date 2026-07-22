@extends('layouts.admin')
@section('title','Employees')
@section('page-title','Employees')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Team Members</h2>
        <p class="text-sm text-gray-400 mt-0.5">{{ $stats['total'] }} total employees</p>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="document.getElementById('import-modal').classList.remove('hidden');document.getElementById('import-modal').classList.add('flex');"
                class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="upload" class="w-4 h-4"></i>
            Import CSV
        </button>
        @include('exports.buttons', ['route' => 'admin.employees.export', 'params' => [$tenant]])
        <a href="{{ route('admin.employees.org-chart', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="git-branch" class="w-4 h-4"></i>
            Org Chart
        </a>
        <a href="{{ route('admin.employees.create', $tenant) }}" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Add Employee
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Total','value'=>$stats['total'],'icon'=>'users','color'=>'bg-brand-50 text-brand-600'],
        ['label'=>'Active','value'=>$stats['active'],'icon'=>'user-check','color'=>'bg-emerald-50 text-emerald-600'],
        ['label'=>'On Leave','value'=>$stats['on_leave'],'icon'=>'calendar-off','color'=>'bg-amber-50 text-amber-600'],
        ['label'=>'Full-Time','value'=>$stats['full_time'],'icon'=>'briefcase','color'=>'bg-purple-50 text-purple-600'],
    ] as $s)
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $s['color'] }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400">{{ $s['label'] }}</p>
            <p class="text-xl font-black text-gray-900">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Filters + Table --}}
<div class="lmt-card p-0 overflow-hidden">
    {{-- Filter bar --}}
    <div class="p-4 border-b border-gray-100 flex flex-wrap items-center gap-3">
        <form action="{{ route('admin.employees.index', $tenant) }}" method="GET" class="flex flex-wrap gap-3 flex-1 mb-0 ">
            <div class="relative flex-1 min-w-48 max-w-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="lmt-input pl-10 py-2 text-sm" placeholder="Search employees…"/>
            </div>
            <select name="department" class="lmt-select py-2 text-sm w-auto min-w-40">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
                @endforeach
            </select>
            <select name="status" class="lmt-select py-2 text-sm w-auto min-w-36">
                <option value="">All Status</option>
                @foreach(['active'=>'Active','on_leave'=>'On Leave','suspended'=>'Suspended','terminated'=>'Terminated'] as $v=>$l)
                <option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
            <button type="submit" class="lmt-btn-primary lmt-btn-sm">Filter</button>
            @if(request()->hasAny(['search','department','status','type']))
            <a href="{{ route('admin.employees.index', $tenant) }}" class="lmt-btn-ghost lmt-btn-sm">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Hire Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                @php
                $statusColors = ['active'=>'lmt-badge-green','on_leave'=>'lmt-badge-amber','suspended'=>'lmt-badge-red','terminated'=>'lmt-badge-gray'];
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                @if($emp->avatar)
                                <img src="{{ $emp->avatar_url }}" class="w-9 h-9 rounded-full object-cover"/>
                                @else
                                <div class="lmt-avatar-sm font-bold text-xs">
                                    {{ substr($emp->first_name,0,1) }}
                                </div>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $emp->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $emp->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td><code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">{{ $emp->employee_code }}</code></td>
                    <td class="text-sm text-gray-600">{{ $emp->department?->name ?? '—' }}</td>
                    <td class="text-sm text-gray-600">{{ $emp->position?->title ?? '—' }}</td>
                    <td>
                        <span class="lmt-badge-gray text-xs capitalize">
                            {{ str_replace('_',' ',$emp->employment_type) }}
                        </span>
                    </td>
                    <td>
                        <span class="{{ $statusColors[$emp->employment_status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ str_replace('_',' ',$emp->employment_status) }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500">{{ $emp->hire_date->format('M j, Y') }}</td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.employees.show', [$tenant, $emp->id]) }}"
                               class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </a>
                            <a href="{{ route('admin.employees.edit', [$tenant, $emp->id]) }}"
                               class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="users" class="w-7 h-7 text-gray-300"></i>
                        </div>
                        <p class="font-semibold text-gray-500 mb-1">No employees found</p>
                        <p class="text-sm text-gray-400 mb-4">Add your first employee to get started</p>
                        <a href="{{ route('admin.employees.create', $tenant) }}" class="lmt-btn-primary lmt-btn-sm inline-flex">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            Add Employee
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $employees->links() }}</div>
    @endif
</div>

{{-- Import Modal --}}
<div id="import-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-4">Import Employees (CSV)</h3>
        <div class="lmt-alert lmt-alert-info mb-4 text-xs">
            CSV must have headers: first_name, last_name, email, hire_date, employment_type, base_salary
        </div>
        <form action="{{ route('admin.employees.import', $tenant) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="lmt-label">CSV File</label>
                <input type="file" name="file" accept=".csv" required class="lmt-input py-2"/>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Import</button>
                <button type="button"
                        onclick="document.getElementById('import-modal').classList.add('hidden');document.getElementById('import-modal').classList.remove('flex');"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush