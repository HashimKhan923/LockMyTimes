@extends('layouts.admin')
@section('title','Assets')
@section('page-title','Asset Management')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label'=>'Total Assets',  'value'=>$stats['total'],                               'icon'=>'package',     'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Available',     'value'=>$stats['available'],                            'icon'=>'check-circle','bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Assigned',      'value'=>$stats['assigned'],                             'icon'=>'user-check',  'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Maintenance',   'value'=>$stats['maintenance'],                          'icon'=>'wrench',      'bg'=>'bg-red-50',    'text'=>'text-red-600'],
        ['label'=>'Total Value',   'value'=>'$'.number_format($stats['total_value'],0),     'icon'=>'dollar-sign', 'bg'=>'bg-purple-50', 'text'=>'text-purple-600'],
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
        @foreach(['all'=>'All','available'=>'Available','assigned'=>'Assigned','maintenance'=>'Maintenance','retired'=>'Retired'] as $val=>$label)
        <a href="{{ route('admin.assets.index', $tenant) }}?status={{ $val }}"
           class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all
                  {{ $status === $val ? 'lmt-gradient-bg text-white' : 'bg-white border border-gray-200 text-gray-800 hover:border-brand-400' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    <div class="flex items-center gap-2">
        <form action="{{ route('admin.assets.index', $tenant) }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}"/>
            <select name="category" class="lmt-select py-2 text-sm w-auto" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected':'' }}>
                    {{ $cat->name }}
                </option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search assets…" class="lmt-input py-2 text-sm w-44"/>
        </form>
        @include('exports.buttons', ['route' => 'admin.assets.export', 'params' => [$tenant]])
        <a href="{{ route('admin.assets.categories', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="tag" class="w-4 h-4"></i>
            Categories
        </a>
        <button onclick="openModal('add-asset-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Asset
        </button>
    </div>
</div>

{{-- Asset Grid --}}
<div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
    @forelse($assets as $asset)
    @php
    $statusColors = [
        'available'  => ['bg'=>'bg-emerald-100','text'=>'text-emerald-700','dot'=>'bg-emerald-500'],
        'assigned'   => ['bg'=>'bg-amber-100',  'text'=>'text-amber-700',  'dot'=>'bg-amber-500'],
        'maintenance'=> ['bg'=>'bg-red-100',    'text'=>'text-red-700',    'dot'=>'bg-red-500'],
        'retired'    => ['bg'=>'bg-gray-100',   'text'=>'text-gray-800',   'dot'=>'bg-gray-400'],
        'lost'       => ['bg'=>'bg-red-100',    'text'=>'text-red-700',    'dot'=>'bg-red-500'],
    ];
    $sc = $statusColors[$asset->status] ?? $statusColors['available'];
    $conditionColors = ['new'=>'text-emerald-600','good'=>'text-brand-600','fair'=>'text-amber-600','poor'=>'text-orange-600','damaged'=>'text-red-600'];
    @endphp
    <div class="lmt-card p-0 overflow-hidden hover:shadow-md transition-shadow">
        {{-- Image / Placeholder --}}
        <div class="h-36 bg-gray-50 flex items-center justify-center relative overflow-hidden">
            @if($asset->image)
            <img src="{{ asset('storage/'.$asset->image) }}" class="w-full h-full object-cover"/>
            @else
            <div class="text-center">
                <div class="w-12 h-12 rounded-2xl mx-auto mb-2 flex items-center justify-center"
                     style="background:{{ $asset->category->color ?? '#6C7DF7' }}20">
                    <i data-lucide="{{ $asset->category->icon ?? 'package' }}"
                       class="w-6 h-6" style="color:{{ $asset->category->color ?? '#6C7DF7' }}"></i>
                </div>
                <p class="text-xs text-gray-800">{{ $asset->category->name ?? 'Asset' }}</p>
            </div>
            @endif
            {{-- Status badge --}}
            <div class="absolute top-2 right-2">
                <span class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold {{ $sc['bg'] }} {{ $sc['text'] }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                    {{ ucfirst($asset->status) }}
                </span>
            </div>
            {{-- Warranty badge --}}
            @if($asset->is_under_warranty)
            <div class="absolute top-2 left-2">
                <span class="lmt-badge-brand text-xs">Under Warranty</span>
            </div>
            @endif
        </div>

        <div class="p-4">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <p class="font-black text-gray-900 truncate">{{ $asset->name }}</p>
                    <p class="text-xs text-gray-800 mt-0.5">
                        <code>{{ $asset->asset_code }}</code>
                        @if($asset->brand) · {{ $asset->brand }}@endif
                        @if($asset->model) {{ $asset->model }}@endif
                    </p>
                </div>
            </div>

            {{-- Condition --}}
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-800">Condition</span>
                <span class="text-xs font-bold capitalize {{ $conditionColors[$asset->condition] ?? 'text-gray-800' }}">
                    {{ ucfirst($asset->condition) }}
                </span>
            </div>

            {{-- Assigned to --}}
            @if($asset->status === 'assigned' && $asset->currentAssignment)
            <div class="flex items-center gap-2 p-2 bg-amber-50 rounded-xl mb-3">
                <div class="w-6 h-6 rounded-full lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ substr($asset->currentAssignment->employee->first_name ?? 'E', 0, 1) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-amber-800 truncate">
                        {{ $asset->currentAssignment->employee->full_name ?? '—' }}
                    </p>
                    <p class="text-[10px] text-amber-600">
                        Since {{ $asset->currentAssignment->assigned_at->format('M j, Y') }}
                    </p>
                </div>
            </div>
            @endif

            {{-- Purchase value --}}
            @if($asset->purchase_cost)
            <p class="text-xs text-gray-800 mb-3">
                Value: <span class="font-bold text-gray-800">${{ number_format($asset->purchase_cost, 0) }}</span>
            </p>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.assets.show', [$tenant, $asset->id]) }}"
                   class="flex-1 lmt-btn-secondary lmt-btn-sm text-center">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    View
                </a>
                @if($asset->status === 'available')
                <button onclick="openAssignModal({{ $asset->id }}, '{{ addslashes($asset->name) }}')"
                        class="flex-1 lmt-btn-primary lmt-btn-sm">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                    Assign
                </button>
                @elseif($asset->status === 'assigned')
                <button onclick="openReturnModal({{ $asset->id }}, '{{ addslashes($asset->name) }}')"
                        class="flex-1 lmt-btn-sm text-center font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl transition-colors">
                    <i data-lucide="corner-up-left" class="w-3.5 h-3.5 inline mr-1"></i>
                    Return
                </button>
                @endif
            </div>
            <div class="flex items-center gap-2 mt-2">
                <button onclick="openEditAssetModal({{ Js::from([
                            'id' => $asset->id,
                            'name' => $asset->name,
                            'category_id' => $asset->category_id,
                            'location_id' => $asset->location_id,
                            'status' => $asset->status,
                            'condition' => $asset->condition,
                            'serial_number' => $asset->serial_number,
                            'brand' => $asset->brand,
                            'model' => $asset->model,
                            'purchase_cost' => $asset->purchase_cost,
                            'purchase_date' => $asset->purchase_date?->toDateString(),
                            'warranty_until' => $asset->warranty_until?->toDateString(),
                            'vendor' => $asset->vendor,
                            'notes' => $asset->notes,
                        ]) }})"
                        class="flex-1 lmt-btn-sm text-center font-semibold text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                    <i data-lucide="pencil" class="w-3.5 h-3.5 inline mr-1"></i>
                    Edit
                </button>
                @if($asset->status !== 'assigned')
                <form action="{{ route('admin.assets.destroy', [$tenant, $asset->id]) }}" method="POST"
                      class="flex-1" onsubmit="return confirm('Delete {{ addslashes($asset->name) }}? This cannot be undone.');">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="w-full lmt-btn-sm text-center font-semibold text-red-500 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 inline mr-1"></i>
                        Delete
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-16 md:col-span-4">
        <i data-lucide="package" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
        <p class="font-black text-gray-800 text-lg">No assets found</p>
        <p class="text-sm text-gray-800 mt-1 mb-5">Add your first asset to get started</p>
        <button onclick="openModal('add-asset-modal')" class="lmt-btn-primary lmt-btn-sm inline-flex">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Asset
        </button>
    </div>
    @endforelse
</div>

@if($assets->hasPages())
<div class="mt-5">{{ $assets->links() }}</div>
@endif

{{-- ============================================================
     MODALS
============================================================ --}}

{{-- Add Asset Modal --}}
<div id="add-asset-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-2xl w-full">
        <h3 class="font-black text-gray-900 mb-5">Add New Asset</h3>
        <form action="{{ route('admin.assets.store', $tenant) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Asset Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. MacBook Pro 14-inch"/>
                </div>
                <div>
                    <label class="lmt-label">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Location</label>
                    <select name="location_id" class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Brand</label>
                    <input type="text" name="brand" class="lmt-input" placeholder="e.g. Apple"/>
                </div>
                <div>
                    <label class="lmt-label">Model</label>
                    <input type="text" name="model" class="lmt-input" placeholder="e.g. MNW83LL/A"/>
                </div>
                <div>
                    <label class="lmt-label">Serial Number</label>
                    <input type="text" name="serial_number" class="lmt-input" placeholder="SN123456789"/>
                </div>
                <div>
                    <label class="lmt-label">Condition <span class="text-red-500">*</span></label>
                    <select name="condition" required class="lmt-select">
                        @foreach(['new'=>'New','good'=>'Good','fair'=>'Fair','damaged'=>'Damaged','retired'=>'Retired'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Purchase Cost ($)</label>
                    <input type="number" name="purchase_cost" step="0.01" min="0" class="lmt-input" placeholder="0.00"/>
                </div>
                <div>
                    <label class="lmt-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Warranty Until</label>
                    <input type="date" name="warranty_until" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Vendor</label>
                    <input type="text" name="vendor" class="lmt-input" placeholder="e.g. Apple Store"/>
                </div>
                <div>
                    <label class="lmt-label">Invoice #</label>
                    <input type="text" name="invoice_number" class="lmt-input" placeholder="INV-2026-001"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Description</label>
                    <textarea name="description" class="lmt-textarea" rows="2"
                              placeholder="Optional details…"></textarea>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Image</label>
                    <input type="file" name="image" accept="image/*" class="lmt-input py-2 text-sm"/>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Add Asset</button>
                <button type="button" onclick="closeModal('add-asset-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Assign Modal --}}
<div id="assign-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-1">Assign Asset</h3>
        <p class="text-sm text-gray-800 mb-5" id="assign-asset-name"></p>
        <form id="assign-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Assign To <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select Employee —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Assignment Date <span class="text-red-500">*</span></label>
                <input type="date" name="assigned_at" required class="lmt-input"
                       value="{{ today()->toDateString() }}"/>
            </div>
            <div>
                <label class="lmt-label">Condition at Assignment <span class="text-red-500">*</span></label>
                <select name="condition_at_assignment" required class="lmt-select">
                    @foreach(['new'=>'New','good'=>'Good','fair'=>'Fair','damaged'=>'Damaged','retired'=>'Retired'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Notes</label>
                <textarea name="assignment_notes" class="lmt-textarea" rows="2"
                          placeholder="Accessories included, special instructions…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Assign Asset</button>
                <button type="button" onclick="closeModal('assign-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Asset Modal --}}
<div id="edit-asset-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-2xl w-full">
        <h3 class="font-black text-gray-900 mb-5">Edit Asset</h3>
        <form id="edit-asset-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Asset Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="edit-asset-name" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" id="edit-asset-category" required class="lmt-select">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Location</label>
                    <select name="location_id" id="edit-asset-location" class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="edit-asset-status" required class="lmt-select">
                        @foreach(['available'=>'Available','assigned'=>'Assigned','maintenance'=>'Maintenance','retired'=>'Retired','lost'=>'Lost'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Condition <span class="text-red-500">*</span></label>
                    <select name="condition" id="edit-asset-condition" required class="lmt-select">
                        @foreach(['new'=>'New','good'=>'Good','fair'=>'Fair','damaged'=>'Damaged','retired'=>'Retired'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Brand</label>
                    <input type="text" name="brand" id="edit-asset-brand" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Model</label>
                    <input type="text" name="model" id="edit-asset-model" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Serial Number</label>
                    <input type="text" name="serial_number" id="edit-asset-serial" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Purchase Cost ($)</label>
                    <input type="number" name="purchase_cost" id="edit-asset-cost" step="0.01" min="0" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Purchase Date</label>
                    <input type="date" name="purchase_date" id="edit-asset-purchase-date" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Warranty Until</label>
                    <input type="date" name="warranty_until" id="edit-asset-warranty" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Vendor</label>
                    <input type="text" name="vendor" id="edit-asset-vendor" class="lmt-input"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Notes</label>
                    <textarea name="notes" id="edit-asset-notes" class="lmt-textarea" rows="2"></textarea>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Save Changes</button>
                <button type="button" onclick="closeModal('edit-asset-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Return Modal --}}
<div id="return-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-1">Return Asset</h3>
        <p class="text-sm text-gray-800 mb-5" id="return-asset-name"></p>
        <form id="return-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Condition at Return <span class="text-red-500">*</span></label>
                <select name="condition_at_return" required class="lmt-select">
                    @foreach(['new'=>'New','good'=>'Good','fair'=>'Fair','damaged'=>'Damaged','retired'=>'Retired'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Return Notes</label>
                <textarea name="return_notes" class="lmt-textarea" rows="2"
                          placeholder="Any damage, missing accessories…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Confirm Return</button>
                <button type="button" onclick="closeModal('return-modal')"
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

function openAssignModal(assetId, assetName) {
    document.getElementById('assign-form').action = `/t/{{ $tenant }}/admin/assets/${assetId}/assign`;
    document.getElementById('assign-asset-name').textContent = assetName;
    openModal('assign-modal');
}
function openReturnModal(assetId, assetName) {
    document.getElementById('return-form').action = `/t/{{ $tenant }}/admin/assets/${assetId}/return`;
    document.getElementById('return-asset-name').textContent = assetName;
    openModal('return-modal');
}
function openEditAssetModal(asset) {
    document.getElementById('edit-asset-form').action = `/t/{{ $tenant }}/admin/assets/${asset.id}`;
    document.getElementById('edit-asset-name').value = asset.name ?? '';
    document.getElementById('edit-asset-category').value = asset.category_id ?? '';
    document.getElementById('edit-asset-location').value = asset.location_id ?? '';
    document.getElementById('edit-asset-status').value = asset.status ?? 'available';
    document.getElementById('edit-asset-condition').value = asset.condition ?? 'good';
    document.getElementById('edit-asset-brand').value = asset.brand ?? '';
    document.getElementById('edit-asset-model').value = asset.model ?? '';
    document.getElementById('edit-asset-serial').value = asset.serial_number ?? '';
    document.getElementById('edit-asset-cost').value = asset.purchase_cost ?? '';
    document.getElementById('edit-asset-purchase-date').value = asset.purchase_date ?? '';
    document.getElementById('edit-asset-warranty').value = asset.warranty_until ?? '';
    document.getElementById('edit-asset-vendor').value = asset.vendor ?? '';
    document.getElementById('edit-asset-notes').value = asset.notes ?? '';
    openModal('edit-asset-modal');
}
['add-asset-modal','assign-modal','return-modal','edit-asset-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush