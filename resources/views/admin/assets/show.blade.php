@extends('layouts.admin')
@section('title', $asset->name)
@section('page-title', 'Asset Detail')

@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.assets.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-700 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Assets
    </a>
    <div class="flex items-center gap-2">
        <button onclick="openModal('edit-asset-modal')" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            Edit
        </button>
        @if($asset->status === 'available')
        <button onclick="openModal('assign-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Assign
        </button>
        @elseif($asset->status === 'assigned')
        <button onclick="openModal('return-modal')"
                class="lmt-btn-sm font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl px-4 py-2 inline-flex items-center gap-2 transition-colors">
            <i data-lucide="corner-up-left" class="w-4 h-4"></i>
            Return Asset
        </button>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left --}}
    <div class="space-y-5">

        {{-- Asset card --}}
        <div class="lmt-card p-0 overflow-hidden">
            {{-- Image --}}
            <div class="h-48 bg-gray-50 flex items-center justify-center relative">
                @if($asset->image)
                <img src="{{ asset('storage/'.$asset->image) }}" class="w-full h-full object-cover"/>
                @else
                <div class="text-center">
                    <div class="w-16 h-16 rounded-2xl mx-auto mb-2 flex items-center justify-center"
                         style="background:{{ $asset->category->color ?? '#6C7DF7' }}20">
                        <i data-lucide="{{ $asset->category->icon ?? 'package' }}"
                           class="w-8 h-8" style="color:{{ $asset->category->color ?? '#6C7DF7' }}"></i>
                    </div>
                    <p class="text-sm text-gray-800">{{ $asset->category->name }}</p>
                </div>
                @endif
                {{-- Status --}}
                @php
                $statusColors = ['available'=>'bg-emerald-500','assigned'=>'bg-amber-500','maintenance'=>'bg-red-500','retired'=>'bg-gray-400'];
                @endphp
                <div class="absolute bottom-3 left-3">
                    <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold text-white {{ $statusColors[$asset->status] ?? 'bg-gray-400' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
                        {{ ucfirst($asset->status) }}
                    </span>
                </div>
            </div>

            <div class="p-5">
                <h2 class="text-xl font-black text-gray-900 mb-1">{{ $asset->name }}</h2>
                <p class="text-sm text-gray-800 mb-4">
                    <code class="bg-gray-100 px-2 py-0.5 rounded font-mono text-xs">{{ $asset->asset_code }}</code>
                </p>

                <div class="space-y-2">
                    @foreach([
                        ['Category',   $asset->category->name ?? '—'],
                        ['Brand',      $asset->brand ?? '—'],
                        ['Model',      $asset->model ?? '—'],
                        ['Serial No.', $asset->serial_number ?? '—'],
                        ['Location',   $asset->location->name ?? '—'],
                        ['Condition',  ucfirst($asset->condition)],
                    ] as [$k,$v])
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-none">
                        <span class="text-xs text-gray-800">{{ $k }}</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $v }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Financial info --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-4">Financial Details</h3>
            <div class="space-y-2">
                @foreach([
                    ['Purchase Cost',  $asset->purchase_cost ? '$'.number_format($asset->purchase_cost,2) : '—'],
                    ['Purchase Date',  $asset->purchase_date?->format('M j, Y') ?? '—'],
                    ['Warranty Until', $asset->warranty_until ? $asset->warranty_until->format('M j, Y').($asset->is_under_warranty ? ' ' : ' ️') : '—'],
                    ['Vendor',         $asset->vendor ?? '—'],
                    ['Invoice #',      $asset->invoice_number ?? '—'],
                ] as [$k,$v])
                <div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-none">
                    <span class="text-xs text-gray-800">{{ $k }}</span>
                    <span class="text-xs font-semibold text-gray-700">{{ $v }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Right --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Current assignment --}}
        @if($asset->status === 'assigned' && $asset->currentAssignment)
        @php $ca = $asset->currentAssignment; @endphp
        <div class="lmt-card" style="border:1.5px solid #FEF3C7; background:#FFFBEB;">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="user-check" class="w-5 h-5 text-amber-600"></i>
                <h3 class="font-black text-amber-900">Currently Assigned</h3>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl lmt-gradient-bg flex items-center justify-center text-white text-xl font-black">
                    {{ substr($ca->employee->first_name ?? 'E', 0, 1) }}
                </div>
                <div class="flex-1">
                    <p class="font-black text-gray-900 text-lg">{{ $ca->employee->full_name }}</p>
                    <p class="text-sm text-gray-800">{{ $ca->employee->department?->name }} · {{ $ca->employee->position?->title }}</p>
                    <p class="text-xs text-amber-700 mt-1">
                        Since {{ $ca->assigned_at->format('M j, Y') }}
                        ({{ $ca->assigned_at->diffForHumans() }})
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-800 mb-1">Condition at Assignment</p>
                    <span class="lmt-badge-gray text-xs capitalize">{{ $ca->condition_at_assignment }}</span>
                </div>
            </div>
            @if($ca->assignment_notes)
            <div class="mt-3 p-3 bg-amber-100 rounded-xl">
                <p class="text-xs text-amber-800">{{ $ca->assignment_notes }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Assignment history --}}
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-black text-gray-900">Assignment History</h3>
                <span class="lmt-badge-gray text-xs">{{ $asset->assignments->count() }} records</span>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($asset->assignments->sortByDesc('assigned_at') as $assignment)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">
                                {{ substr($assignment->employee->first_name ?? 'E', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $assignment->employee->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-800">
                                    {{ $assignment->assigned_at->format('M j, Y') }}
                                    @if($assignment->returned_at)
                                     {{ $assignment->returned_at->format('M j, Y') }}
                                    <span class="text-gray-800">({{ $assignment->assigned_at->diffInDays($assignment->returned_at) }} days)</span>
                                    @else
                                    <span class="text-amber-600 font-semibold"> Present</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right text-xs flex-shrink-0">
                            <div class="flex items-center gap-2 justify-end">
                                <span class="lmt-badge-gray capitalize">{{ $assignment->condition_at_assignment }}</span>
                                @if($assignment->condition_at_return)
                                <i data-lucide="arrow-right" class="w-3 h-3 text-gray-800"></i>
                                <span class="lmt-badge-gray capitalize">{{ $assignment->condition_at_return }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($assignment->return_notes)
                    <p class="text-xs text-gray-800 mt-2 pl-9">{{ $assignment->return_notes }}</p>
                    @endif
                </div>
                @empty
                <div class="text-center py-10">
                    <i data-lucide="history" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
                    <p class="text-sm text-gray-800">No assignment history</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Notes --}}
        @if($asset->description || $asset->notes)
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-3">Notes</h3>
            @if($asset->description)
            <p class="text-sm text-gray-700 mb-2">{{ $asset->description }}</p>
            @endif
            @if($asset->notes)
            <p class="text-sm text-gray-800">{{ $asset->notes }}</p>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- Assign Modal --}}
<div id="assign-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Assign {{ $asset->name }}</h3>
        <form action="{{ route('admin.assets.assign', [$tenant, $asset->id]) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Assign To <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select Employee —</option>
                    @foreach(\App\Models\Tenant\Employee::active()->orderBy('first_name')->get() as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Date <span class="text-red-500">*</span></label>
                <input type="date" name="assigned_at" required class="lmt-input" value="{{ today()->toDateString() }}"/>
            </div>
            <div>
                <label class="lmt-label">Condition <span class="text-red-500">*</span></label>
                <select name="condition_at_assignment" required class="lmt-select">
                    @foreach(['new'=>'New','good'=>'Good','fair'=>'Fair','damaged'=>'Damaged','retired'=>'Retired'] as $v=>$l)
                    <option value="{{ $v }}" {{ $v===$asset->condition?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Notes</label>
                <textarea name="assignment_notes" class="lmt-textarea" rows="2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Assign</button>
                <button type="button" onclick="closeModal('assign-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Return Modal --}}
<div id="return-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Return {{ $asset->name }}</h3>
        <form action="{{ route('admin.assets.return', [$tenant, $asset->id]) }}" method="POST" class="space-y-4">
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
                <textarea name="return_notes" class="lmt-textarea" rows="2" placeholder="Damage, missing parts…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Confirm Return</button>
                <button type="button" onclick="closeModal('return-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-asset-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Edit Asset</h3>
        <form action="{{ route('admin.assets.update', [$tenant, $asset->id]) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Name</label>
                    <input type="text" name="name" required class="lmt-input" value="{{ $asset->name }}"/>
                </div>
                <div>
                    <label class="lmt-label">Category</label>
                    <select name="category_id" required class="lmt-select">
                        @foreach(\App\Models\Tenant\AssetCategory::where('is_active',true)->get() as $cat)
                        <option value="{{ $cat->id }}" {{ $cat->id===$asset->category_id?'selected':'' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Status</label>
                    <select name="status" required class="lmt-select">
                        @foreach(['available'=>'Available','maintenance'=>'Maintenance','retired'=>'Retired','lost'=>'Lost'] as $v=>$l)
                        <option value="{{ $v }}" {{ $v===$asset->status?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Condition</label>
                    <select name="condition" required class="lmt-select">
                        @foreach(['new'=>'New','good'=>'Good','fair'=>'Fair','damaged'=>'Damaged','retired'=>'Retired'] as $v=>$l)
                        <option value="{{ $v }}" {{ $v===$asset->condition?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Warranty Until</label>
                    <input type="date" name="warranty_until" class="lmt-input"
                           value="{{ $asset->warranty_until?->toDateString() }}"/>
                </div>
                <div>
                    <label class="lmt-label">Purchase Cost</label>
                    <input type="number" name="purchase_cost" step="0.01" min="0" class="lmt-input"
                           value="{{ $asset->purchase_cost }}"/>
                </div>
                <div>
                    <label class="lmt-label">Vendor</label>
                    <input type="text" name="vendor" class="lmt-input" value="{{ $asset->vendor }}"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Notes</label>
                    <textarea name="notes" class="lmt-textarea" rows="2">{{ $asset->notes }}</textarea>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save Changes</button>
                <button type="button" onclick="closeModal('edit-asset-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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
['assign-modal','return-modal','edit-asset-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush