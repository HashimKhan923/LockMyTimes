@extends('layouts.admin')
@section('title','Documents')
@section('page-title','Document Management')

@section('content')

{{-- =====================================================
     STATS
===================================================== --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label'=>'Total',         'value'=>$stats['total'],        'icon'=>'files',     'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Policies',      'value'=>$stats['policies'],     'icon'=>'shield',    'bg'=>'bg-purple-50', 'text'=>'text-purple-600'],
        ['label'=>'Contracts',     'value'=>$stats['contracts'],    'icon'=>'file-text', 'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Expiring 30d',  'value'=>$stats['expiring'],     'icon'=>'clock',     'bg'=>'bg-red-50',    'text'=>'text-red-600'],
        ['label'=>'Pending Sign',  'value'=>$stats['pending_sign'], 'icon'=>'pen-line',  'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
    ] as $s)
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} {{ $s['text'] }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $s['label'] }}</p>
            <p class="text-xl font-black text-gray-900">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- =====================================================
     MAIN LAYOUT: Sidebar + Content
===================================================== --}}
<div class="flex gap-6 items-start">

    {{-- =====================================================
         LEFT SIDEBAR — fixed width 220px
    ===================================================== --}}
    <div class="w-56 flex-shrink-0">
        <div class="lmt-card p-0 overflow-hidden">

            {{-- Sidebar header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <span class="text-xs font-bold text-gray-800 uppercase tracking-wider">Folders</span>
                <button onclick="openModal('add-folder-modal')"
                        class="w-6 h-6 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="plus" class="w-3 h-3"></i>
                </button>
            </div>

            {{-- All Documents --}}
            <a href="{{ route('admin.documents.index', $tenant) }}"
               class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-semibold transition-colors
                      {{ !$folderId && !request('category') ? 'bg-brand-50 text-brand-700' : 'text-gray-800 hover:bg-gray-50' }}">
                <i data-lucide="layout-grid" class="w-4 h-4 flex-shrink-0"></i>
                <span class="truncate">All Documents</span>
            </a>

            {{-- Folders list --}}
            @if($allFolders->isNotEmpty())
            <div class="border-t border-gray-50 pt-1 pb-1">
                @foreach($allFolders->where('parent_id', null) as $folder)
                <a href="{{ route('admin.documents.index', $tenant) }}?folder={{ $folder->id }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-sm font-medium transition-colors
                          {{ $folderId == $folder->id ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-800 hover:bg-gray-50' }}">
                    <i data-lucide="{{ $folder->icon ?? 'folder' }}" class="w-4 h-4 flex-shrink-0"
                       style="color:{{ $folder->color ?? '#6C7DF7' }}"></i>
                    <span class="flex-1 truncate text-xs">{{ $folder->name }}</span>
                    <span class="text-xs text-gray-800 flex-shrink-0">{{ $folder->documents_count }}</span>
                </a>
                @foreach($allFolders->where('parent_id', $folder->id) as $sub)
                <a href="{{ route('admin.documents.index', $tenant) }}?folder={{ $sub->id }}"
                   class="flex items-center gap-2 pl-9 pr-4 py-1.5 text-xs font-medium transition-colors
                          {{ $folderId == $sub->id ? 'text-brand-700 font-semibold bg-brand-50' : 'text-gray-800 hover:bg-gray-50' }}">
                    <i data-lucide="folder" class="w-3 h-3 flex-shrink-0" style="color:{{ $sub->color ?? '#6C7DF7' }}"></i>
                    <span class="flex-1 truncate">{{ $sub->name }}</span>
                    <span class="text-gray-800">{{ $sub->documents_count }}</span>
                </a>
                @endforeach
                @endforeach
            </div>
            @endif

            {{-- By Category --}}
            <div class="border-t border-gray-100 pt-1 pb-2">
                <p class="px-4 py-1.5 text-[10px] font-bold text-gray-800 uppercase tracking-widest">By Category</p>
                @foreach([
                    'policy'   => ['shield',           'text-purple-500'],
                    'contract' => ['file-text',         'text-amber-500'],
                    'template' => ['layout-template',   'text-brand-500'],
                    'form'     => ['clipboard-list',    'text-emerald-500'],
                    'handbook' => ['book-open',          'text-blue-500'],
                    'other'    => ['file',               'text-gray-800'],
                ] as $cat => [$icon, $color])
                <a href="{{ route('admin.documents.index', $tenant) }}?category={{ $cat }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium transition-colors capitalize
                          {{ request('category') === $cat ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-800 hover:bg-gray-50' }}">
                    <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5 flex-shrink-0 {{ $color }}"></i>
                    {{ str_replace('_', ' ', $cat) }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- =====================================================
         RIGHT CONTENT — takes remaining space
    ===================================================== --}}
    <div class="flex-1 min-w-0">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-3 mb-4">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-1.5 text-sm min-w-0">
                <a href="{{ route('admin.documents.index', $tenant) }}"
                   class="text-gray-800 hover:text-brand-600 transition-colors flex-shrink-0">
                    <i data-lucide="home" class="w-4 h-4"></i>
                </a>
                @foreach($breadcrumbs as $bc)
                <span class="text-gray-800 flex-shrink-0">/</span>
                <a href="{{ route('admin.documents.index', $tenant) }}?folder={{ $bc->id }}"
                   class="font-semibold text-gray-800 hover:text-brand-600 transition-colors truncate">
                    {{ $bc->name }}
                </a>
                @endforeach
                @if(request('category'))
                <span class="text-gray-800 flex-shrink-0">/</span>
                <span class="font-semibold text-gray-800 capitalize">{{ request('category') }}</span>
                @endif
            </div>

            {{-- Search + Upload --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <form action="{{ route('admin.documents.index', $tenant) }}" method="GET" class="flex items-center gap-2">
                    @if($folderId)<input type="hidden" name="folder" value="{{ $folderId }}"/>@endif
                    <div class="relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-gray-800 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search…"
                               class="lmt-input pl-8 py-2 text-sm w-40"/>
                    </div>
                    <input type="date" name="from" value="{{ request('from') }}" title="Uploaded from" class="lmt-input py-2 text-sm w-auto"/>
                    <input type="date" name="to" value="{{ request('to') }}" title="Uploaded to" class="lmt-input py-2 text-sm w-auto"/>
                    <button type="submit" class="lmt-btn-secondary lmt-btn-sm">Filter</button>
                </form>
                <button onclick="openModal('upload-modal')" class="lmt-btn-primary lmt-btn-sm flex-shrink-0">
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    Upload
                </button>
            </div>
        </div>

        {{-- File explorer table: folders first, then documents --}}
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">

            @if($documents->isEmpty() && $folders->isEmpty())
            <div class="text-center py-20">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="folder-open" class="w-8 h-8 text-gray-800"></i>
                </div>
                <p class="font-black text-gray-800 text-lg mb-1">No documents here</p>
                <p class="text-sm text-gray-800 mb-5">Upload your first document to get started</p>
                <button onclick="openModal('upload-modal')" class="lmt-btn-primary lmt-btn-sm inline-flex">
                    <i data-lucide="upload" class="w-4 h-4"></i> Upload Document
                </button>
            </div>
            @else

            {{-- Header --}}
            <div class="flex items-center px-5 py-3 bg-gray-50 border-b border-gray-100">
                <div class="flex-1 min-w-0 text-xs font-bold text-gray-800 uppercase tracking-wider">Name</div>
                <div class="w-28 flex-shrink-0 text-xs font-bold text-gray-800 uppercase tracking-wider">Category</div>
                <div class="w-32 flex-shrink-0 text-xs font-bold text-gray-800 uppercase tracking-wider">Date</div>
                <div class="w-24 flex-shrink-0 text-xs font-bold text-gray-800 uppercase tracking-wider text-right">Actions</div>
            </div>

            <div class="divide-y divide-gray-100">

                {{-- ── Folder rows ── --}}
                @foreach($folders as $folder)
                <a href="{{ route('admin.documents.index', $tenant) }}?folder={{ $folder->id }}"
                   class="flex items-center px-5 py-3 gap-4 hover:bg-gray-50 transition-colors group">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:{{ $folder->color ?? '#6C7DF7' }}18;">
                            <svg viewBox="0 0 24 24" style="width:1.1rem;height:1.1rem;" fill="{{ $folder->color ?? '#6C7DF7' }}">
                                <path d="M3 7C3 5.9 3.9 5 5 5H9.586A2 2 0 0 1 11 5.586L12.414 7H19C20.1 7 21 7.9 21 9V17C21 18.1 20.1 19 19 19H5C3.9 19 3 18.1 3 17V7Z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate">{{ $folder->name }}</p>
                            <p class="text-xs text-gray-800">{{ $folder->documents_count }} {{ Str::plural('item', $folder->documents_count) }}</p>
                        </div>
                    </div>
                    <div class="w-28 flex-shrink-0">
                        <span class="text-xs text-gray-800">Folder</span>
                    </div>
                    <div class="w-32 flex-shrink-0 text-xs text-gray-800">{{ $folder->updated_at->format('M j, Y') }}</div>
                    <div class="w-24 flex-shrink-0 flex justify-end">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-800 group-hover:text-brand-400 transition-colors"></i>
                    </div>
                </a>
                @endforeach

                {{-- ── Document rows ── --}}
                @foreach($documents as $doc)
                @php
                    $catBg = [
                        'policy'   => 'bg-purple-100 text-purple-700',
                        'contract' => 'bg-amber-100 text-amber-700',
                        'template' => 'bg-blue-100 text-blue-700',
                        'form'     => 'bg-emerald-100 text-emerald-700',
                        'handbook' => 'bg-indigo-100 text-indigo-700',
                        'other'    => 'bg-gray-100 text-gray-800',
                    ][$doc->category] ?? 'bg-gray-100 text-gray-800';

                    $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));
                    [$extBg, $extColor] = match($ext) {
                        'pdf'                           => ['#FEF2F2','#EF4444'],
                        'doc','docx'                    => ['#EFF6FF','#3B82F6'],
                        'xls','xlsx'                    => ['#ECFDF5','#10B981'],
                        'jpg','jpeg','png','gif','webp'  => ['#F5F3FF','#8B5CF6'],
                        'zip','rar'                     => ['#FFFBEB','#F59E0B'],
                        default                         => ['#F3F4F6','#6B7280'],
                    };
                @endphp
                <div class="flex items-center px-5 py-3 gap-4 hover:bg-gray-50 transition-colors
                            {{ $doc->is_expired ? 'bg-red-50' : ($doc->is_expiring_soon ? 'bg-amber-50/60' : '') }}">

                    {{-- Name --}}
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-[10px] font-bold uppercase tracking-wide"
                             style="background:{{ $extBg }}; color:{{ $extColor }};">
                            {{ $ext ?: 'file' }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate">{{ $doc->title }}</p>
                            <p class="text-xs text-gray-800 truncate">
                                {{ $doc->file_size_human }}
                                @if($doc->uploader) · {{ $doc->uploader->name }}@endif
                                @if($doc->expiry_date)
                                · <span class="{{ $doc->is_expired ? 'text-red-500 font-semibold' : ($doc->is_expiring_soon ? 'text-amber-500 font-semibold' : '') }}">Exp {{ $doc->expiry_date->format('M j, Y') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="w-28 flex-shrink-0">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold capitalize {{ $catBg }}">
                            {{ ucfirst($doc->category) }}
                        </span>
                    </div>

                    {{-- Date --}}
                    <div class="w-32 flex-shrink-0 text-xs text-gray-800">
                        {{ $doc->created_at->format('M j, Y') }}
                    </div>

                    {{-- Actions --}}
                    <div class="w-24 flex-shrink-0 flex items-center justify-end gap-1.5">
                        <a href="{{ route('admin.documents.download', [$tenant, $doc->id]) }}"
                           class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors"
                           title="Download">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        </a>
                        @if($doc->requires_signature || $doc->requires_acknowledgment)
                        <button onclick="openSendModal({{ $doc->id }}, '{{ addslashes($doc->title) }}')"
                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                title="Send for Signature">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </button>
                        @endif
                        <form action="{{ route('admin.documents.destroy', [$tenant, $doc->id]) }}"
                              method="POST" onsubmit="return confirm('Delete \'{{ addslashes($doc->title) }}\'?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"
                                    title="Delete">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            @if($documents->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $documents->links() }}</div>
            @endif
            @endif
        </div>
    </div>
</div>

{{-- =====================================================
     UPLOAD MODAL
===================================================== --}}
<div id="upload-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Upload Document</h3>
            <button onclick="closeModal('upload-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:text-gray-800 hover:bg-gray-100 flex items-center justify-center transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.documents.store', $tenant) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf

            {{-- File picker first --}}
            <div>
                <label class="lmt-label">File <span class="text-red-500">*</span></label>
                <input type="file" name="file" required class="lmt-input py-2 text-sm"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip"/>
                <p class="lmt-help">PDF, Word, Excel, PowerPoint, Images — up to 50MB</p>
            </div>

            <div>
                <label class="lmt-label">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="lmt-input"
                       placeholder="e.g. Employee Handbook 2026"/>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Category <span class="text-red-500">*</span></label>
                    <select name="category" required class="lmt-select">
                        @foreach(['policy'=>'Policy','contract'=>'Contract','template'=>'Template','form'=>'Form','handbook'=>'Handbook','other'=>'Other'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Visibility <span class="text-red-500">*</span></label>
                    <select name="visibility" required class="lmt-select">
                        @foreach(['public'=>'Public','private'=>'Private','role_based'=>'Role Based','employee_specific'=>'Employee Specific'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="lmt-label">Folder</label>
                <select name="folder_id" class="lmt-select">
                    <option value="">— Root (No Folder) —</option>
                    @foreach($allFolders as $f)
                    <option value="{{ $f->id }}" {{ $folderId == $f->id ? 'selected' : '' }}>
                        {{ $f->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2"
                          placeholder="Optional summary…"></textarea>
            </div>

            <div>
                <label class="lmt-label">Expiry Date</label>
                <input type="date" name="expiry_date" class="lmt-input"/>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2.5 cursor-pointer p-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                    <input type="checkbox" name="requires_acknowledgment" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-sm font-medium text-gray-800">Requires Acknowledgment</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer p-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                    <input type="checkbox" name="requires_signature" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-sm font-medium text-gray-800">Requires Signature</span>
                </label>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" class="lmt-btn-primary flex-1">Upload Document</button>
                <button type="button" onclick="closeModal('upload-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- =====================================================
     ADD FOLDER MODAL
===================================================== --}}
<div id="add-folder-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-sm">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Create Folder</h3>
            <button onclick="closeModal('add-folder-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.documents.folders.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Folder Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="lmt-input" placeholder="e.g. HR Policies"/>
            </div>
            <div>
                <label class="lmt-label">Parent Folder</label>
                <select name="parent_id" class="lmt-select">
                    <option value="">— Root Level —</option>
                    @foreach($allFolders as $f)
                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">Icon (Lucide name)</label>
                    <input type="text" name="icon" class="lmt-input" placeholder="folder"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Visibility</label>
                <select name="visibility" required class="lmt-select">
                    <option value="private">Private</option>
                    <option value="public">Public</option>
                    <option value="role_based">Role Based</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create Folder</button>
                <button type="button" onclick="closeModal('add-folder-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- =====================================================
     SEND FOR SIGNATURE MODAL
===================================================== --}}
<div id="send-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-black text-gray-900">Send for Signature</h3>
            <button onclick="closeModal('send-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <p class="text-sm text-gray-800 mb-5" id="send-doc-name"></p>
        <form id="send-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Select Employees <span class="text-red-500">*</span></label>
                <div class="max-h-52 overflow-y-auto border border-gray-200 rounded-xl p-2 space-y-1">
                    @foreach($employees as $emp)
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                               class="w-4 h-4 rounded"/>
                        <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">
                            {{ substr($emp->first_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $emp->full_name }}</p>
                            <p class="text-xs text-gray-800">{{ $emp->department?->name }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Send</button>
                <button type="button" onclick="closeModal('send-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

function openSendModal(docId, docName) {
    document.getElementById('send-form').action =
        `/t/{{ $tenant }}/admin/documents/${docId}/send`;
    document.getElementById('send-doc-name').textContent = docName;
    document.querySelectorAll('#send-form input[type=checkbox]')
        .forEach(cb => cb.checked = false);
    openModal('send-modal');
}

// Close on backdrop click
['upload-modal', 'add-folder-modal', 'send-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush