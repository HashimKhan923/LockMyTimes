@extends('layouts.admin')
@section('title','Expense Categories')
@section('page-title','Expense Categories')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Expense Categories</h2>
        <p class="text-sm text-gray-800">{{ $categories->count() }} categories configured</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.expenses.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back
        </a>
        <button onclick="openModal('add-cat-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Category
        </button>
    </div>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($categories as $cat)
    <div class="lmt-card">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-sm"
                     style="background:{{ $cat->color ?? '#6C7DF7' }}">
                    {{ substr($cat->code, 0, 2) }}
                </div>
                <div>
                    <h3 class="font-black text-gray-900">{{ $cat->name }}</h3>
                    <p class="text-xs text-gray-800">{{ $cat->code }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="openEditCat({{ $cat->id }}, {{ json_encode($cat) }})"
                        class="w-7 h-7 rounded-lg bg-gray-100 text-gray-800 hover:bg-brand-50 hover:text-brand-600 flex items-center justify-center transition-colors">
                    <i data-lucide="pencil" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-3 text-center">
            <div class="bg-gray-50 rounded-xl p-2">
                <p class="font-black text-gray-900 text-lg">{{ $cat->expenses_count }}</p>
                <p class="text-xs text-gray-800">Expenses</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-2">
                <p class="font-black text-gray-900 text-sm">
                    {{ $cat->max_amount ? '$'.number_format($cat->max_amount,0) : '∞' }}
                </p>
                <p class="text-xs text-gray-800">Max Amount</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-1.5">
            @if($cat->requires_receipt)
            <span class="lmt-badge-amber text-xs">Receipt Required</span>
            @endif
            @if($cat->gl_code)
            <span class="lmt-badge-gray text-xs">GL: {{ $cat->gl_code }}</span>
            @endif
            <span class="{{ $cat->is_active ? 'lmt-badge-green' : 'lmt-badge-red' }} text-xs">
                {{ $cat->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        @if($cat->description)
        <p class="text-xs text-gray-800 mt-2">{{ $cat->description }}</p>
        @endif
    </div>
    @empty
    <div class="lmt-card text-center py-12 md:col-span-3">
        <i data-lucide="tag" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
        <p class="text-gray-800">No categories yet</p>
    </div>
    @endforelse
</div>

{{-- Add Modal --}}
<div id="add-cat-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Expense Category</h3>
        <form action="{{ route('admin.expenses.categories.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. Travel"/>
                </div>
                <div>
                    <label class="lmt-label">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required class="lmt-input" placeholder="TRV"/>
                </div>
                <div>
                    <label class="lmt-label">Max Amount ($)</label>
                    <input type="number" name="max_amount" step="0.01" class="lmt-input" placeholder="No limit"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">GL Code</label>
                    <input type="text" name="gl_code" class="lmt-input" placeholder="6000"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2"></textarea>
            </div>
            <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                <input type="checkbox" name="requires_receipt" value="1" class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-700">Receipt Required</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button" onclick="closeModal('add-cat-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-cat-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Edit Category</h3>
        <form id="edit-cat-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name</label>
                    <input type="text" name="name" id="edit-cat-name" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" id="edit-cat-color" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">Max Amount ($)</label>
                    <input type="number" name="max_amount" id="edit-cat-max" step="0.01" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Status</label>
                    <select name="is_active" class="lmt-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                <input type="checkbox" name="requires_receipt" value="1" id="edit-cat-receipt" class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-700">Receipt Required</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save</button>
                <button type="button" onclick="closeModal('edit-cat-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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
function openEditCat(id, cat) {
    document.getElementById('edit-cat-form').action = `/t/{{ $tenant }}/admin/expenses/categories/${id}`;
    document.getElementById('edit-cat-name').value    = cat.name;
    document.getElementById('edit-cat-color').value   = cat.color || '#6C7DF7';
    document.getElementById('edit-cat-max').value     = cat.max_amount || '';
    document.getElementById('edit-cat-receipt').checked = !!cat.requires_receipt;
    openModal('edit-cat-modal');
}
['add-cat-modal','edit-cat-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush