@extends('layouts.admin')
@section('title','Positions')
@section('page-title','Positions')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-black text-gray-900">Positions & Job Titles</h2>
    <button onclick="document.getElementById('add-pos-modal').classList.remove('hidden');document.getElementById('add-pos-modal').classList.add('flex');"
            class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Position
    </button>
</div>

<div class="lmt-card p-0 overflow-hidden">
    <table class="lmt-table">
        <thead><tr><th>Title</th><th>Department</th><th>Employees</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($positions as $pos)
        <tr>
            <td class="font-semibold text-gray-900 text-sm">{{ $pos->title }}</td>
            <td class="text-sm text-gray-800">{{ $pos->department?->name ?? '—' }}</td>
            <td class="text-sm font-semibold text-gray-900">{{ $pos->employees_count }}</td>
            <td>
                <form action="{{ route('admin.positions.destroy', [$tenant, $pos->id]) }}" method="POST"
                      onsubmit="return confirm('Delete this position?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"
                            {{ $pos->employees_count > 0 ? 'disabled' : '' }}>
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center py-12 text-gray-800">No positions yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Add Modal --}}
<div id="add-pos-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Position</h3>
        <form action="{{ route('admin.positions.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="lmt-input" placeholder="e.g. Senior Software Engineer"/>
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
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button" onclick="document.getElementById('add-pos-modal').classList.add('hidden');document.getElementById('add-pos-modal').classList.remove('flex');" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush