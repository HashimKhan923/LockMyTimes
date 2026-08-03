@extends('layouts.admin')
@section('title','QR Codes')
@section('page-title','QR Codes')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">QR Code Management</h2>
        <p class="text-sm text-gray-400 mt-0.5">Generate location-bound QR codes for geo-fenced clock-in</p>
    </div>
    <button onclick="document.getElementById('add-modal').classList.remove('hidden');document.getElementById('add-modal').classList.add('flex');"
            class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Generate QR Code
    </button>
</div>

{{-- How it works banner --}}
<div class="lmt-card mb-6 bg-brand-500 text-white">
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <i data-lucide="info" class="w-5 h-5 text-white"></i>
        </div>
        <div>
            <h3 class="font-black mb-1">How QR Attendance Works</h3>
            <p class="text-white/80 text-sm leading-relaxed">
                1. Generate a QR code for each office location below.<br>
                2. Print and post it at the entrance (or display on a screen).<br>
                3. Employees scan it with the Lockmytimes mobile app — GPS is verified against the location's geofence.<br>
                4. Clock-in is recorded instantly with timestamp, coordinates, and device info.
            </p>
        </div>
    </div>
</div>

{{-- QR Code cards --}}
@if($locations->isEmpty())
<div class="lmt-card text-center py-16">
    <i data-lucide="map-pin" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
    <p class="font-semibold text-gray-500 mb-1">No locations set up yet</p>
    <p class="text-sm text-gray-400 mb-4">You need at least one location before generating QR codes</p>
    <a href="{{ route('admin.locations.index', $tenant) }}" class="lmt-btn-primary lmt-btn-sm inline-flex">
        <i data-lucide="map-pin" class="w-4 h-4"></i>
        Add Location First
    </a>
</div>
@else

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($qrCodes as $qr)
    <div class="lmt-card">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="font-black text-gray-900">{{ $qr->label ?? $qr->location->name }}</h3>
                <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                    {{ $qr->location->name }}
                </p>
            </div>
            <span class="{{ $qr->is_active ? 'lmt-badge-green' : 'lmt-badge-red' }} text-xs">
                {{ $qr->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        {{-- QR Preview --}}
        <div class="flex justify-center my-4 p-3 bg-white rounded-xl border border-gray-100">
            {!! app(\App\Services\QrCodeService::class)->generate($qr->token, 150) !!}
        </div>

        {{-- Token --}}
        <div class="bg-gray-50 rounded-xl p-3 mb-4">
            <p class="text-xs text-gray-400 mb-1">Token</p>
            <code class="text-xs font-mono text-gray-700 break-all">{{ substr($qr->token, 0, 20) }}…</code>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-2 text-center mb-4">
            <div class="bg-gray-50 rounded-xl p-2">
                <p class="text-sm font-black text-gray-900">{{ number_format($qr->scan_count) }}</p>
                <p class="text-xs text-gray-400">Scans</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-2">
                <p class="text-sm font-black text-gray-900">{{ $qr->location->geofence_radius_meters }}m</p>
                <p class="text-xs text-gray-400">Radius</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.qrcodes.show', [$tenant, $qr->id]) }}"
               class="lmt-btn-primary lmt-btn-sm flex-1 justify-center">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print
            </a>
            <button type="button" class="lmt-btn-secondary lmt-btn-sm" title="Edit"
                    onclick='openEditQrModal(@json($qr))'>
                <i data-lucide="pencil" class="w-4 h-4"></i>
            </button>
            <form action="{{ route('admin.qrcodes.rotate', [$tenant, $qr->id]) }}" method="POST">
                @csrf @method('PATCH')
                <button type="submit" class="lmt-btn-secondary lmt-btn-sm"
                        onclick="return confirm('Rotate token? The old QR code will stop working immediately.')"
                        title="Rotate token">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                </button>
            </form>
            <form action="{{ route('admin.qrcodes.toggle', [$tenant, $qr->id]) }}" method="POST">
                @csrf @method('PATCH')
                <button type="submit" class="lmt-btn-secondary lmt-btn-sm"
                        title="{{ $qr->is_active ? 'Deactivate' : 'Activate' }}">
                    <i data-lucide="{{ $qr->is_active ? 'pause' : 'play' }}" class="w-4 h-4"></i>
                </button>
            </form>
            <form action="{{ route('admin.qrcodes.destroy', [$tenant, $qr->id]) }}" method="POST"
                  onsubmit="return confirm('Delete this QR code?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </form>
        </div>

        @if($qr->last_rotated_at)
        <p class="text-xs text-gray-400 mt-2 text-center">
            Last rotated: {{ $qr->last_rotated_at->diffForHumans() }}
        </p>
        @endif
    </div>
    @empty
    <div class="lmt-card text-center py-16 md:col-span-3">
        <i data-lucide="qr-code" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
        <p class="font-semibold text-gray-500 mb-4">No QR codes yet</p>
        <button onclick="document.getElementById('add-modal').classList.remove('hidden');document.getElementById('add-modal').classList.add('flex');"
                class="lmt-btn-primary lmt-btn-sm inline-flex">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Generate First QR
        </button>
    </div>
    @endforelse
</div>
@endif

{{-- Add Modal --}}
<div id="add-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Generate QR Code</h3>
        <form action="{{ route('admin.qrcodes.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Location <span class="text-red-500">*</span></label>
                <select name="location_id" required class="lmt-select">
                    <option value="">— Select Location —</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->geofence_radius_meters }}m radius)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Label (optional)</label>
                <input type="text" name="label" class="lmt-input" placeholder="e.g. Main Entrance, 2nd Floor"/>
            </div>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-gray-50">
                    <input type="checkbox" name="rotate_token" value="1" class="w-4 h-4 rounded"/>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Daily Token Rotation</p>
                        <p class="text-xs text-gray-400">Token auto-rotates daily (prevents screenshot fraud)</p>
                    </div>
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Generate QR Code</button>
                <button type="button"
                        onclick="document.getElementById('add-modal').classList.add('hidden');document.getElementById('add-modal').classList.remove('flex');"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Edit QR Code</h3>
        <form id="edit-qr-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="location_id" id="edit-qr-location-id"/>
            <div>
                <label class="lmt-label">Location</label>
                <select class="lmt-select" disabled>
                    <option id="edit-qr-location-name"></option>
                </select>
                <p class="lmt-help">Location can't be changed after creation — delete and generate a new QR instead.</p>
            </div>
            <div>
                <label class="lmt-label">Label (optional)</label>
                <input type="text" name="label" id="edit-qr-label" class="lmt-input" placeholder="e.g. Main Entrance, 2nd Floor"/>
            </div>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-gray-50">
                    <input type="checkbox" name="rotate_token" id="edit-qr-rotate-token" value="1" class="w-4 h-4 rounded"/>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Daily Token Rotation</p>
                        <p class="text-xs text-gray-400">Token auto-rotates daily (prevents screenshot fraud)</p>
                    </div>
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Save Changes</button>
                <button type="button" onclick="closeEditQrModal()" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });

const qrUpdateUrlTemplate = @json(route('admin.qrcodes.update', [$tenant, '__ID__']));

function openEditQrModal(qr) {
    document.getElementById('edit-qr-form').action = qrUpdateUrlTemplate.replace('__ID__', qr.id);
    document.getElementById('edit-qr-location-id').value = qr.location_id;
    document.getElementById('edit-qr-location-name').textContent = qr.location ? qr.location.name : '';
    document.getElementById('edit-qr-label').value = qr.label || '';
    document.getElementById('edit-qr-rotate-token').checked = !!qr.rotate_token;

    const modal = document.getElementById('edit-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (window.lucide) lucide.createIcons();
}

function closeEditQrModal() {
    const modal = document.getElementById('edit-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush