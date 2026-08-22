@extends('layouts.admin')
@section('title','Roles & Permissions')
@section('page-title','Roles & Permissions')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Total Roles',       'value'=>$roles->count(),      'icon'=>'shield',        'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Total Users',       'value'=>$totalUsers,          'icon'=>'users',         'bg'=>'bg-purple-50', 'text'=>'text-purple-600'],
        ['label'=>'Permissions',       'value'=>$totalPermissions,    'icon'=>'key',           'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Modules Covered',   'value'=>$totalModules,        'icon'=>'layout-grid',   'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
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
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-black text-gray-900">All Roles</h2>
        <p class="text-sm text-gray-800">Manage access levels and permissions across your organization</p>
    </div>
    <button onclick="openModal('add-role-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Create Custom Role
    </button>
</div>

{{-- Role Cards --}}
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @php
    $protectedRoles = ['Tenant Admin','super_admin'];
    $systemRoles    = ['Tenant Admin','super_admin','Employee','HR Manager','Manager'];
    $roleColors = [
        'Tenant Admin' => ['bg'=>'bg-red-50',    'text'=>'text-red-600',    'icon'=>'crown'],
        'HR Manager'   => ['bg'=>'bg-brand-50',  'text'=>'text-brand-600',  'icon'=>'briefcase'],
        'Manager'      => ['bg'=>'bg-amber-50',  'text'=>'text-amber-600',  'icon'=>'user-cog'],
        'Employee'     => ['bg'=>'bg-emerald-50','text'=>'text-emerald-600','icon'=>'user'],
    ];
    @endphp
    @foreach($roles as $role)
    @php
    $rc = $roleColors[$role->name] ?? ['bg'=>'bg-purple-50','text'=>'text-purple-600','icon'=>'shield'];
    $userCount = $userCounts[$role->id] ?? 0;
    $isProtected = in_array($role->name, $protectedRoles);
    $isSystem    = in_array($role->name, $systemRoles);
    @endphp
    <div class="lmt-card hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl {{ $rc['bg'] }} {{ $rc['text'] }} flex items-center justify-center flex-shrink-0">
                    <i data-lucide="{{ $rc['icon'] }}" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-black text-gray-900">{{ str_replace('Tenant Admin', 'Organization Admin', $role->name) }}</h3>
                    @if($isProtected)
                    <span class="lmt-badge-red text-xs">Full Access</span>
                    @elseif($isSystem)
                    <span class="lmt-badge-gray text-xs">System Role</span>
                    @else
                    <span class="lmt-badge-brand text-xs">Custom Role</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-gray-50 rounded-xl p-3 text-center">
                <p class="text-lg font-black text-gray-900">{{ $role->permissions_count }}</p>
                <p class="text-xs text-gray-800">Permissions</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 text-center">
                <p class="text-lg font-black text-gray-900">{{ $userCount }}</p>
                <p class="text-xs text-gray-800">User{{ $userCount !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.roles.show', [$tenant, $role->id]) }}"
               class="flex-1 lmt-btn-primary lmt-btn-sm text-center">
                <i data-lucide="key" class="w-3.5 h-3.5"></i>
                Manage Permissions
            </a>
            @if(! in_array($role->name, ['Tenant Admin','super_admin','Employee','HR Manager','Manager']))
            <form action="{{ route('admin.roles.destroy', [$tenant, $role->id]) }}"
                  method="POST" onsubmit="return confirm('Delete the \'{{ addslashes($role->name) }}\' role?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="w-9 h-9 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors flex-shrink-0">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Add Role Modal --}}
<div id="add-role-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Create Custom Role</h3>
            <button onclick="closeModal('add-role-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.roles.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Role Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="lmt-input"
                       placeholder="e.g. Finance Officer, Team Lead…"/>
                <p class="lmt-help">You can assign specific permissions after creating the role.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create Role</button>
                <button type="button" onclick="closeModal('add-role-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
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
document.getElementById('add-role-modal')?.addEventListener('click', function(e) { if(e.target===this) closeModal('add-role-modal'); });
</script>
@endpush