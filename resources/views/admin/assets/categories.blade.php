@extends('layouts.admin')
@section('title','Asset Categories')
@section('page-title','Asset Categories')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Asset Categories</h2>
        <p class="text-sm text-gray-400">{{ $categories->count() }} categories</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.assets.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
        <button onclick="openModal('add-cat-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Category
        </button>
    </div>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
    @forelse($categories as $cat)
    <div class="lmt-card text-center">
        <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center"
             style="background:{{ $cat->color ?? '#6C7DF7' }}20">
            <i data-lucide="{{ $cat->icon ?? 'package' }}"
               class="w-7 h-7" style="color:{{ $cat->color ?? '#6C7DF7' }}"></i>
        </div>
        <h3 class="font-black text-gray-900">{{ $cat->name }}</h3>
        <p class="text-xs text-gray-400 mt-0.5 mb-3">{{ $cat->code }}</p>
        <p class="text-3xl font-black mb-1" style="color:{{ $cat->color ?? '#6C7DF7' }}">
            {{ $cat->assets_count }}
        </p>
        <p class="text-xs text-gray-400 mb-3">assets</p>
        @if($cat->description)
        <p class="text-xs text-gray-400">{{ $cat->description }}</p>
        @endif
    </div>
    @empty
    <div class="lmt-card text-center py-12 md:col-span-4">
        <i data-lucide="tag" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
        <p class="text-gray-400">No categories yet</p>
    </div>
    @endforelse
</div>

{{-- Add Category Modal --}}
<div id="add-cat-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Asset Category</h3>
        <form action="{{ route('admin.assets.categories.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. Laptops"/>
                </div>
                <div>
                    <label class="lmt-label">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required class="lmt-input" placeholder="LAP"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">Icon (Lucide name)</label>
                    <input type="text" name="icon" class="lmt-input" placeholder="e.g. laptop, phone, monitor"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button" onclick="closeModal('add-cat-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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
document.getElementById('add-cat-modal')?.addEventListener('click', function(e) { if(e.target===this) closeModal('add-cat-modal'); });
</script>
@endpush