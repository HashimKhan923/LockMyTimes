@extends('layouts.admin')
@section('title','Departments')
@section('page-title','Departments')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-black text-gray-900">Departments</h2>
    <button onclick="document.getElementById('add-dept-modal').classList.remove('hidden');document.getElementById('add-dept-modal').classList.add('flex');"
            class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Department
    </button>
</div>

<div class="lmt-card p-0 overflow-hidden">
    <table class="lmt-table">
        <thead><tr><th>Name</th><th>Code</th><th>Manager</th><th>Employees</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($departments as $dept)
        <tr>
            <td>
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full" style="background:{{ $dept->color }}"></div>
                    <span class="font-semibold text-gray-900 text-sm">{{ $dept->name }}</span>
                    @if($dept->parent) <span class="lmt-badge-gray text-xs">↑ {{ $dept->parent->name }}</span> @endif
                </div>
            </td>
            <td><code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">{{ $dept->code ?? '—' }}</code></td>
            <td class="text-sm text-gray-600">{{ $dept->manager?->full_name ?? '—' }}</td>
            <td class="text-sm font-semibold text-gray-900">{{ $dept->employees_count }}</td>
            <td><span class="{{ $dept->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">{{ $dept->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td>
                <button onclick="openEditDept({{ $dept->id }}, '{{ addslashes($dept->name) }}', '{{ $dept->code }}', '{{ $dept->color }}')"
                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center py-12 text-gray-400">
            <i data-lucide="git-branch" class="w-8 h-8 mx-auto mb-2 text-gray-200"></i>
            No departments yet
        </td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Add Modal --}}
<div id="add-dept-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Department</h3>
        <form action="{{ route('admin.departments.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="lmt-input" placeholder="e.g. Engineering"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Code</label>
                    <input type="text" name="code" class="lmt-input" placeholder="ENG"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Manager</label>
                <select name="manager_id" class="lmt-select">
                    <option value="">— No Manager —</option>
                    @foreach(\App\Models\Tenant\Employee::active()->orderBy('first_name')->get() as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button" onclick="document.getElementById('add-dept-modal').classList.add('hidden');document.getElementById('add-dept-modal').classList.remove('flex');" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush