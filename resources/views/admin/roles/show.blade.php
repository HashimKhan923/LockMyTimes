@extends('layouts.admin')
@section('title', str_replace('Tenant Admin', 'Organization Admin', $role->name))
@section('page-title', 'Role: ' . str_replace('Tenant Admin', 'Organization Admin', $role->name))

@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.roles.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        All Roles
    </a>
    @if(!in_array($role->name, ['Tenant Admin','super_admin','Employee']))
    <button onclick="openModal('rename-modal')" class="lmt-btn-secondary lmt-btn-sm">
        <i data-lucide="pencil" class="w-4 h-4"></i>
        Rename Role
    </button>
    @endif
</div>

{{-- Role header --}}
<div class="lmt-card mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl lmt-gradient-bg flex items-center justify-center text-white flex-shrink-0">
                <i data-lucide="shield" class="w-7 h-7"></i>
            </div>
            <div>
                <h2 class="text-xl font-black text-gray-900">{{ str_replace('Tenant Admin', 'Organization Admin', $role->name) }}</h2>
                <p class="text-sm text-gray-800">
                    {{ count($rolePermissions) }} permission{{ count($rolePermissions) !== 1 ? 's' : '' }} ·
                    {{ $users->count() }} user{{ $users->count() !== 1 ? 's' : '' }} assigned
                </p>
            </div>
        </div>
        @if($isProtected)
        <span class="lmt-badge-red font-bold">
            <i data-lucide="lock" class="w-3.5 h-3.5 inline mr-1"></i>
            Full System Access — Not Editable
        </span>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left — Permission Matrix --}}
    <div class="lg:col-span-2">
        <form id="permissions-form"
              action="{{ route('admin.roles.permissions.update', [$tenant, $role->id]) }}"
              method="POST">
            @csrf @method('PATCH')

            <div class="lmt-card p-0 overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-gray-100">
                    <h3 class="font-black text-gray-900">Permission Matrix</h3>
                    @if(!$isProtected)
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="toggleAll(true)"
                                class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                            Select All
                        </button>
                        <span class="text-gray-800">|</span>
                        <button type="button" onclick="toggleAll(false)"
                                class="text-xs font-semibold text-gray-800 hover:text-gray-800">
                            Clear All
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Column headers (actions) --}}
                <div class="flex items-center px-4 py-2.5 bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-800 uppercase tracking-wider">
                    <div class="flex-1 min-w-0">Module</div>
                    @foreach(['view'=>'View','create'=>'Create','edit'=>'Edit','delete'=>'Delete','approve'=>'Approve','export'=>'Export'] as $action=>$label)
                    <div class="w-16 flex-shrink-0 text-center">{{ $label }}</div>
                    @endforeach
                </div>

                {{-- Sections --}}
                <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                    @foreach($matrix as $sectionName => $modules)
                    @if(! empty($modules))
                    <div>
                        {{-- Section header --}}
                        <div class="px-4 py-2 bg-brand-50/40">
                            <p class="text-xs font-bold text-brand-700 uppercase tracking-wider">{{ $sectionName }}</p>
                        </div>

                        {{-- Module rows --}}
                        @foreach($modules as $moduleKey => $module)
                        <div class="flex items-center px-4 py-2.5 hover:bg-gray-50 transition-colors border-t border-gray-50">
                            <div class="flex-1 min-w-0 flex items-center gap-2">
                                <input type="checkbox"
                                       class="row-toggle w-3.5 h-3.5 rounded border-gray-300"
                                       data-row="{{ $moduleKey }}"
                                       onchange="toggleRow('{{ $moduleKey }}', this.checked)"
                                       {{ $isProtected ? 'disabled' : '' }}/>
                                <span class="text-sm font-medium text-gray-800">{{ $module['label'] }}</span>
                            </div>
                            @foreach(['view','create','edit','delete','approve','export'] as $action)
                            <div class="w-16 flex-shrink-0 text-center">
                                @if(isset($module['actions'][$action]))
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $module['actions'][$action] }}"
                                       class="perm-checkbox w-4 h-4 rounded border-gray-300 cursor-pointer"
                                       data-row="{{ $moduleKey }}"
                                       data-action="{{ $action }}"
                                       {{ in_array($module['actions'][$action], $rolePermissions) ? 'checked' : '' }}
                                       {{ $isProtected ? 'disabled' : '' }}
                                       onchange="syncRowToggle('{{ $moduleKey }}')"/>
                                @else
                                <span class="text-gray-200">—</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @endforeach
                </div>

                @if(!$isProtected)
                <div class="p-4 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                    <p class="text-xs text-gray-800">
                        <span id="selected-count">{{ count($rolePermissions) }}</span> permission(s) selected
                    </p>
                    <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Permissions
                    </button>
                </div>
                @endif
            </div>
        </form>
    </div>

    {{-- Right — Users with this role --}}
    <div class="space-y-5">
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-black text-gray-900">Assigned Users</h3>
                <button onclick="openModal('assign-user-modal')"
                        class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @forelse($users as $user)
                <div class="flex items-center gap-3 p-3">
                    <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 text-sm truncate">{{ $user->name }}</p>
                        <p class="text-xs text-gray-800 truncate">{{ $user->email }}</p>
                    </div>
                    @if($role->name !== 'Tenant Admin' && $role->name !== 'super_admin')
                    <form action="{{ route('admin.roles.remove', $tenant) }}" method="POST"
                          onsubmit="return confirm('Remove this role from {{ addslashes($user->name) }}?')">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}"/>
                        <input type="hidden" name="role" value="{{ $role->name }}"/>
                        <button type="submit"
                                class="w-7 h-7 rounded-lg text-gray-800 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors flex-shrink-0">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                    @endif
                </div>
                @empty
                <div class="text-center py-10">
                    <i data-lucide="user-x" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
                    <p class="text-sm text-gray-800">No users assigned yet</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Quick legend --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-3 text-sm">Permission Legend</h3>
            <div class="space-y-2 text-xs text-gray-800">
                <p><strong class="text-gray-800">View</strong> — See records and data</p>
                <p><strong class="text-gray-800">Create</strong> — Add new records</p>
                <p><strong class="text-gray-800">Edit</strong> — Modify existing records</p>
                <p><strong class="text-gray-800">Delete</strong> — Remove records permanently</p>
                <p><strong class="text-gray-800">Approve</strong> — Approve requests/submissions</p>
                <p><strong class="text-gray-800">Export</strong> — Download/export data</p>
            </div>
        </div>
    </div>
</div>

{{-- Assign User Modal --}}
<div id="assign-user-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Assign User to {{ str_replace('Tenant Admin', 'Organization Admin', $role->name) }}</h3>
            <button onclick="closeModal('assign-user-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.roles.assign', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="role" value="{{ $role->name }}"/>
            <div>
                <label class="lmt-label">Select User <span class="text-red-500">*</span></label>
                <select name="user_id" required class="lmt-select">
                    <option value="">— Select User —</option>
                    @foreach(\App\Models\Tenant\User::orderBy('name')->get() as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
                <p class="lmt-help">This will replace the user's current role with "{{ str_replace('Tenant Admin', 'Organization Admin', $role->name) }}".</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Assign Role</button>
                <button type="button" onclick="closeModal('assign-user-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Rename Role Modal --}}
@if(!in_array($role->name, ['Tenant Admin','super_admin','Employee']))
<div id="rename-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Rename Role</h3>
            <button onclick="closeModal('rename-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.roles.update', [$tenant, $role->id]) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="lmt-label">Role Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="lmt-input" value="{{ $role->name }}"/>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save</button>
                <button type="button" onclick="closeModal('rename-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    // Initialize row-toggle checkboxes based on current state
    document.querySelectorAll('.row-toggle').forEach(rowToggle => {
        syncRowToggle(rowToggle.dataset.row);
    });
    updateSelectedCount();
});

function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }

// Toggle entire row (all actions for a module)
function toggleRow(moduleKey, checked) {
    document.querySelectorAll(`.perm-checkbox[data-row="${moduleKey}"]`).forEach(cb => {
        cb.checked = checked;
    });
    updateSelectedCount();
}

// Sync the row-toggle checkbox state based on individual checkboxes
function syncRowToggle(moduleKey) {
    const checkboxes = document.querySelectorAll(`.perm-checkbox[data-row="${moduleKey}"]`);
    const rowToggle  = document.querySelector(`.row-toggle[data-row="${moduleKey}"]`);
    if (!rowToggle || checkboxes.length === 0) return;

    const checkedCount = [...checkboxes].filter(cb => cb.checked).length;
    rowToggle.checked    = checkedCount === checkboxes.length;
    rowToggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;

    updateSelectedCount();
}

// Select / clear all permissions
function toggleAll(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = checked);
    document.querySelectorAll('.row-toggle').forEach(cb => {
        cb.checked = checked;
        cb.indeterminate = false;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.perm-checkbox:checked').length;
    const el = document.getElementById('selected-count');
    if (el) el.textContent = count;
}

['assign-user-modal','rename-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush