@extends('layouts.admin')
@section('title','Locations')
@section('page-title','Work Locations')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #location-map { height: 320px; width: 100%; border-radius: 16px; z-index: 1; display: block; }
    .leaflet-container { font-family: inherit; }
    .map-search-box { position: absolute; top: 12px; left: 12px; right: 12px; z-index: 1000; }
    .map-search-results { background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); max-height: 180px; overflow-y: auto; margin-top: 4px; }
    .map-search-result-item { padding: 10px 14px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
    .map-search-result-item:last-child { border-bottom: none; }
    .map-search-result-item:hover { background: #f0f9ff; }
    .map-wrapper { position: relative; }
    .radius-ring { transition: all 0.2s; }
    .coord-pill {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,0.95); backdrop-filter: blur(8px);
        border-radius: 999px; padding: 4px 12px; font-size: 11px;
        color: #374151; box-shadow: 0 2px 12px rgba(0,0,0,0.12);
        border: 1px solid rgba(255,255,255,0.8);
        position: absolute; bottom: 12px; left: 12px; z-index: 1000; pointer-events: none;
    }
    .coord-dot { width: 7px; height: 7px; background: #6366f1; border-radius: 50%; }
    .lmt-modal { max-height: 90vh; overflow-y: auto; }
    .radius-slider-wrap { display: flex; align-items: center; gap: 12px; }
    .radius-slider-wrap input[type=range] {
        flex: 1; height: 5px; -webkit-appearance: none; appearance: none;
        background: linear-gradient(to right, #6366f1 0%, #6366f1 var(--fill, 2%), #e5e7eb var(--fill, 2%), #e5e7eb 100%);
        border-radius: 99px; outline: none; cursor: pointer;
    }
    .radius-slider-wrap input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%;
        background: #6366f1; border: 3px solid white; box-shadow: 0 0 0 1px #6366f1;
        cursor: pointer; transition: box-shadow 0.15s;
    }
    .radius-slider-wrap input[type=range]::-webkit-slider-thumb:hover { box-shadow: 0 0 0 4px rgba(99,102,241,0.2); }
    #map-pin-hint { transition: opacity 0.4s; }
</style>
@endpush

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-black text-gray-900">Work Locations</h2>
    <button onclick="openAddModal()"
            class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Location
    </button>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($locations as $loc)
    <div class="lmt-card">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                <i data-lucide="{{ $loc->is_headquarters ? 'building' : 'map-pin' }}" class="w-5 h-5"></i>
            </div>
            <div class="flex items-center gap-2">
                @if($loc->is_headquarters)
                <span class="lmt-badge-brand text-xs">HQ</span>
                @endif
                <span class="{{ $loc->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                    {{ $loc->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
        <h3 class="font-black text-gray-900 mb-1">{{ $loc->name }}</h3>
        @if($loc->address_line1)
        <p class="text-xs text-gray-800 mb-3">{{ $loc->address_line1 }}, {{ $loc->city }}, {{ $loc->state }}</p>
        @endif
        <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="bg-gray-50 rounded-xl p-2 text-center">
                <p class="font-black text-gray-900 text-base">{{ $loc->employees_count }}</p>
                <p class="text-gray-800">Employees</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-2 text-center">
                <p class="font-black text-gray-900 text-base">{{ $loc->geofence_radius_meters }}m</p>
                <p class="text-gray-800">Geo-fence</p>
            </div>
        </div>
        @if($loc->latitude && $loc->longitude)
        <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-800">
            <i data-lucide="crosshair" class="w-3.5 h-3.5"></i>
            {{ $loc->latitude }}, {{ $loc->longitude }}
        </div>
        @endif
        <div class="mt-4 pt-3 border-t border-gray-100">
            <button type="button" onclick='openEditModal(@json($loc))' class="lmt-btn-secondary lmt-btn-sm w-full justify-center">
                <i data-lucide="pencil" class="w-4 h-4"></i>
                Edit
            </button>
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-16 md:col-span-3">
        <i data-lucide="map-pin" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
        <p class="font-semibold text-gray-800 mb-1">No locations yet</p>
        <p class="text-sm text-gray-800 mb-4">Add a location to start generating QR codes for attendance</p>
        <button onclick="openAddModal()" class="lmt-btn-primary lmt-btn-sm inline-flex">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Location
        </button>
    </div>
    @endforelse
</div>

{{-- Add Modal --}}
<div id="add-loc-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-2xl w-full">
        <div class="flex items-center justify-between mb-5">
            <h3 id="loc-modal-title" class="font-black text-gray-900 text-lg">Add Work Location</h3>
            <button type="button" onclick="closeAddModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-800 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="loc-form" action="{{ route('admin.locations.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Location Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="loc-name-input" required class="lmt-input" placeholder="e.g. New York HQ"/>
                </div>
                <div>
                    <label class="lmt-label">Code</label>
                    <input type="text" name="code" id="loc-code-input" class="lmt-input" placeholder="NYC-HQ"/>
                </div>
                <div>
                    <label class="lmt-label">Timezone</label>
                    <select name="timezone" id="loc-timezone-input" class="lmt-select">
                        @foreach(['America/New_York'=>'Eastern','America/Chicago'=>'Central','America/Denver'=>'Mountain','America/Los_Angeles'=>'Pacific','America/Anchorage'=>'Alaska','Pacific/Honolulu'=>'Hawaii'] as $tz=>$label)
                        <option value="{{ $tz }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Address</label>
                    <input type="text" name="address_line1" id="loc-address-input" class="lmt-input" placeholder="Street address"/>
                </div>
                <div>
                    <label class="lmt-label">City</label>
                    <input type="text" name="city" id="loc-city-input" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">State</label>
                    <input type="text" name="state" id="loc-state-input" class="lmt-input" placeholder="NY"/>
                </div>
            </div>

            {{-- MAP SECTION --}}
            <div class="col-span-2">
                <label class="lmt-label mb-2 flex items-center gap-2">
                    <i data-lucide="map" class="w-4 h-4 text-indigo-500"></i>
                    Pin Location on Map
                    <span class="text-xs font-normal text-gray-800">— click or drag the pin</span>
                </label>
                <div class="map-wrapper rounded-2xl overflow-hidden border-2 border-gray-100 shadow-sm" style="background:#f8fafc;">
                    {{-- Search bar overlay --}}
                    <div class="map-search-box">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-800 pointer-events-none" style="z-index:2;"></i>
                            <input id="map-search-input" type="text" placeholder="Search for a place..."
                                class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border-0 shadow-lg bg-white/95 backdrop-blur focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                autocomplete="off"/>
                        </div>
                        <div id="map-search-results" class="map-search-results hidden"></div>
                    </div>
                    <div id="location-map"></div>
                    {{-- Use My Location button --}}
                    <button type="button" id="use-my-location-btn" onclick="useMyLocation()"
                        title="Use my current location"
                        style="position:absolute;top:60px;right:12px;z-index:1000;
                               width:36px;height:36px;border-radius:10px;border:none;cursor:pointer;
                               background:white;box-shadow:0 2px 12px rgba(0,0,0,0.18);
                               display:flex;align-items:center;justify-content:center;
                               transition:all 0.2s;">
                        <i data-lucide="locate-fixed" class="w-4 h-4" style="color:#6366f1;" id="locate-icon"></i>
                    </button>
                    {{-- Live coord pill --}}
                    <div class="coord-pill" id="coord-pill">
                        <span class="coord-dot"></span>
                        <span id="coord-pill-text">No location selected</span>
                    </div>
                </div>
                <p id="map-pin-hint" class="text-xs text-indigo-500 mt-1.5 flex items-center gap-1">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    Click anywhere on the map to drop a pin, or search above
                </p>
            </div>

            {{-- Lat/Lng hidden + readonly display --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Latitude</label>
                    <div class="relative">
                        <input type="number" name="latitude" id="lat-input" step="0.0000001" class="lmt-input pr-8" placeholder="Click map to set"/>
                        <i data-lucide="crosshair" class="w-3.5 h-3.5 absolute right-3 top-1/2 -translate-y-1/2 text-gray-800"></i>
                    </div>
                </div>
                <div>
                    <label class="lmt-label">Longitude</label>
                    <div class="relative">
                        <input type="number" name="longitude" id="lng-input" step="0.0000001" class="lmt-input pr-8" placeholder="Click map to set"/>
                        <i data-lucide="crosshair" class="w-3.5 h-3.5 absolute right-3 top-1/2 -translate-y-1/2 text-gray-800"></i>
                    </div>
                </div>
            </div>

            {{-- Radius with live slider --}}
            <div>
                <label class="lmt-label flex items-center justify-between">
                    <span>Geo-fence Radius</span>
                    <span class="font-black text-indigo-600" id="radius-display">100 m</span>
                </label>
                <div class="radius-slider-wrap mt-2">
                    <i data-lucide="circle" class="w-4 h-4 text-gray-800 shrink-0"></i>
                    <input type="range" id="radius-slider" min="10" max="5000" value="100" step="10"/>
                    <i data-lucide="circle" class="w-6 h-6 text-indigo-400 shrink-0"></i>
                </div>
                <input type="number" name="geofence_radius_meters" id="radius-input" value="100" min="10" max="5000" class="lmt-input mt-2" placeholder="meters"/>
                <p class="lmt-help">Employees must be within this radius to clock in via QR</p>
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_headquarters" id="loc-hq-input" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-sm font-medium text-gray-700">This is the headquarters</span>
                </label>
                <label id="loc-active-wrap" class="items-center gap-2 cursor-pointer hidden">
                    <input type="checkbox" name="is_active" id="loc-active-input" value="1" class="w-4 h-4 rounded" checked/>
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">
                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                    <span id="loc-submit-label">Create Location</span>
                </button>
                <button type="button" onclick="closeAddModal()" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

let locationMap = null;
let locationMarker = null;
let geofenceCircle = null;
let searchTimeout = null;

const locStoreUrl = "{{ route('admin.locations.store', $tenant) }}";
const locUpdateUrlTemplate = @json(route('admin.locations.update', [$tenant, '__ID__']));

function resetLocForm() {
    const form = document.getElementById('loc-form');
    form.reset();
    form.action = locStoreUrl;
    const methodField = form.querySelector('input[name="_method"]');
    if (methodField) methodField.remove();

    document.getElementById('loc-modal-title').textContent = 'Add Work Location';
    document.getElementById('loc-submit-label').textContent = 'Create Location';
    const activeWrap = document.getElementById('loc-active-wrap');
    activeWrap.classList.add('hidden');
    activeWrap.classList.remove('flex');

    clearMapPin();

    document.getElementById('radius-slider').value = 100;
    document.getElementById('radius-input').value = 100;
    updateRadiusLabel(100);
    updateSliderFill(document.getElementById('radius-slider'));
}

function clearMapPin() {
    if (locationMarker) { locationMap.removeLayer(locationMarker); locationMarker = null; }
    if (geofenceCircle) { locationMap.removeLayer(geofenceCircle); geofenceCircle = null; }
    document.getElementById('lat-input').value = '';
    document.getElementById('lng-input').value = '';
    document.getElementById('coord-pill-text').textContent = 'No location selected';
    const hint = document.getElementById('map-pin-hint');
    if (hint) hint.style.opacity = '1';
}

function openAddModal() {
    resetLocForm();
    const modal = document.getElementById('add-loc-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (window.lucide) lucide.createIcons();
    // Give the browser time to render the modal and compute dimensions
    setTimeout(initLocationMap, 150);
}

function openEditModal(loc) {
    resetLocForm();

    document.getElementById('loc-form').action = locUpdateUrlTemplate.replace('__ID__', loc.id);
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'PUT';
    document.getElementById('loc-form').appendChild(methodField);

    document.getElementById('loc-modal-title').textContent = 'Edit Work Location';
    document.getElementById('loc-submit-label').textContent = 'Save Changes';

    document.getElementById('loc-name-input').value = loc.name || '';
    document.getElementById('loc-code-input').value = loc.code || '';
    document.getElementById('loc-timezone-input').value = loc.timezone || '';
    document.getElementById('loc-address-input').value = loc.address_line1 || '';
    document.getElementById('loc-city-input').value = loc.city || '';
    document.getElementById('loc-state-input').value = loc.state || '';
    document.getElementById('loc-hq-input').checked = !!loc.is_headquarters;

    const activeWrap = document.getElementById('loc-active-wrap');
    activeWrap.classList.remove('hidden');
    activeWrap.classList.add('flex');
    document.getElementById('loc-active-input').checked = !!loc.is_active;

    const radius = loc.geofence_radius_meters || 100;
    document.getElementById('radius-slider').value = radius;
    document.getElementById('radius-input').value = radius;
    updateRadiusLabel(radius);

    const modal = document.getElementById('add-loc-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (window.lucide) lucide.createIcons();

    setTimeout(() => {
        initLocationMap();
        updateSliderFill(document.getElementById('radius-slider'));
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        if (!isNaN(lat) && !isNaN(lng)) {
            setPin(lat, lng);
            locationMap.setView([lat, lng], 15);
        } else {
            updateRadiusCircle();
        }
    }, 150);
}

function closeAddModal() {
    const modal = document.getElementById('add-loc-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function initLocationMap() {
    if (locationMap) {
        setTimeout(() => locationMap.invalidateSize(), 50);
        return;
    }

    locationMap = L.map('location-map', {
        center: [20, 0],
        zoom: 2,
        zoomControl: true,
        attributionControl: false,
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(locationMap);

    // Custom marker icon
    const pinIcon = L.divIcon({
        className: '',
        html: `<div style="
            width:36px;height:36px;
            background:linear-gradient(135deg,#6366f1,#8b5cf6);
            border-radius:50% 50% 50% 0;
            transform:rotate(-45deg);
            border:3px solid white;
            box-shadow:0 4px 16px rgba(99,102,241,0.5);
            display:flex;align-items:center;justify-content:center;
        "><div style="transform:rotate(45deg);width:10px;height:10px;background:white;border-radius:50%;"></div></div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -36],
    });

    locationMap.on('click', function(e) {
        setPin(e.latlng.lat, e.latlng.lng, pinIcon);
    });

    // Sync radius from existing inputs
    updateRadiusCircle();

    // Radius slider input circle
    const slider = document.getElementById('radius-slider');
    const radiusInput = document.getElementById('radius-input');

    slider.addEventListener('input', function() {
        radiusInput.value = this.value;
        updateSliderFill(this);
        updateRadiusLabel(this.value);
        updateRadiusCircle();
    });

    radiusInput.addEventListener('input', function() {
        let v = Math.min(5000, Math.max(10, parseInt(this.value) || 10));
        slider.value = v;
        updateSliderFill(slider);
        updateRadiusLabel(v);
        updateRadiusCircle();
    });

    updateSliderFill(slider);

    // Manual lat/lng move map
    document.getElementById('lat-input').addEventListener('change', syncFromInputs);
    document.getElementById('lng-input').addEventListener('change', syncFromInputs);

    // Search
    const searchInput = document.getElementById('map-search-input');
    const searchResults = document.getElementById('map-search-results');

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 3) { searchResults.classList.add('hidden'); return; }
        searchTimeout = setTimeout(() => doSearch(q), 400);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.map-search-box')) searchResults.classList.add('hidden');
    });

    function doSearch(q) {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=5`, {
            headers: { 'Accept-Language': 'en' }
        })
        .then(r => r.json())
        .then(data => {
            searchResults.innerHTML = '';
            if (!data.length) {
                searchResults.innerHTML = '<div class="map-search-result-item text-gray-800">No results found</div>';
            } else {
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'map-search-result-item';
                    div.innerHTML = `<span class="font-medium text-gray-800">${item.display_name.split(',')[0]}</span><br><span class="text-gray-800 text-xs">${item.display_name}</span>`;
                    div.addEventListener('click', () => {
                        const lat = parseFloat(item.lat);
                        const lng = parseFloat(item.lon);
                        setPin(lat, lng, pinIcon);
                        locationMap.setView([lat, lng], 15);
                        searchInput.value = item.display_name.split(',')[0];
                        searchResults.classList.add('hidden');
                    });
                    searchResults.appendChild(div);
                });
            }
            searchResults.classList.remove('hidden');
        })
        .catch(() => { searchResults.classList.add('hidden'); });
    }

    function syncFromInputs() {
        const lat = parseFloat(document.getElementById('lat-input').value);
        const lng = parseFloat(document.getElementById('lng-input').value);
        if (!isNaN(lat) && !isNaN(lng)) {
            setPin(lat, lng, pinIcon);
            locationMap.setView([lat, lng], 15);
        }
    }
}

function setPin(lat, lng, pinIcon) {
    const latInput = document.getElementById('lat-input');
    const lngInput = document.getElementById('lng-input');
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);

    // Update coord pill
    document.getElementById('coord-pill-text').textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;

    // Hide hint
    const hint = document.getElementById('map-pin-hint');
    if (hint) hint.style.opacity = '0';

    if (!pinIcon) {
        pinIcon = L.divIcon({
            className: '',
            html: `<div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 4px 16px rgba(99,102,241,0.5);display:flex;align-items:center;justify-content:center;"><div style="transform:rotate(45deg);width:10px;height:10px;background:white;border-radius:50%;"></div></div>`,
            iconSize: [36, 36], iconAnchor: [18, 36],
        });
    }

    if (locationMarker) {
        locationMarker.setLatLng([lat, lng]);
    } else {
        locationMarker = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(locationMap);
        locationMarker.on('drag', function(e) {
            const pos = e.latlng;
            document.getElementById('lat-input').value = pos.lat.toFixed(7);
            document.getElementById('lng-input').value = pos.lng.toFixed(7);
            document.getElementById('coord-pill-text').textContent = `${pos.lat.toFixed(5)}, ${pos.lng.toFixed(5)}`;
            if (geofenceCircle) geofenceCircle.setLatLng(pos);
        });
        locationMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            document.getElementById('lat-input').value = pos.lat.toFixed(7);
            document.getElementById('lng-input').value = pos.lng.toFixed(7);
        });
    }

    updateRadiusCircle();
}

function updateRadiusCircle() {
    if (!locationMap) return;
    const lat = parseFloat(document.getElementById('lat-input').value);
    const lng = parseFloat(document.getElementById('lng-input').value);
    const radius = parseInt(document.getElementById('radius-input').value) || 100;

    if (isNaN(lat) || isNaN(lng)) return;

    if (geofenceCircle) {
        geofenceCircle.setLatLng([lat, lng]);
        geofenceCircle.setRadius(radius);
    } else {
        geofenceCircle = L.circle([lat, lng], {
            radius: radius,
            color: '#6366f1',
            fillColor: '#6366f1',
            fillOpacity: 0.12,
            weight: 2,
            dashArray: '6 4',
            className: 'radius-ring',
        }).addTo(locationMap);
    }
}

function updateRadiusLabel(val) {
    document.getElementById('radius-display').textContent = val >= 1000 ? `${(val/1000).toFixed(1)} km` : `${val} m`;
}

function updateSliderFill(slider) {
    const min = slider.min, max = slider.max, val = slider.value;
    const pct = ((val - min) / (max - min)) * 100;
    slider.style.setProperty('--fill', pct + '%');
}

function useMyLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    const btn = document.getElementById('use-my-location-btn');
    const icon = document.getElementById('locate-icon');

    // Loading state
    btn.style.background = '#eef2ff';
    icon.style.color = '#a5b4fc';
    icon.setAttribute('data-lucide', 'loader');
    if (window.lucide) lucide.createIcons();
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            // Ensure map is ready
            if (!locationMap) initLocationMap();

            setPin(lat, lng);
            locationMap.setView([lat, lng], 17);

            // Restore button
            btn.style.background = '#e0e7ff';
            icon.style.color = '#6366f1';
            icon.setAttribute('data-lucide', 'locate-fixed');
            if (window.lucide) lucide.createIcons();
            btn.disabled = false;
            setTimeout(() => { btn.style.background = 'white'; }, 1500);
        },
        (err) => {
            let msg = 'Unable to retrieve location.';
            if (err.code === 1) msg = 'Location permission denied. Please allow location access in your browser.';
            if (err.code === 2) msg = 'Location unavailable. Try clicking the map manually.';
            if (err.code === 3) msg = 'Location request timed out. Try again.';
            alert(msg);

            btn.style.background = '#fee2e2';
            icon.style.color = '#f87171';
            icon.setAttribute('data-lucide', 'locate-off');
            if (window.lucide) lucide.createIcons();
            btn.disabled = false;
            setTimeout(() => { btn.style.background = 'white'; icon.style.color = '#6366f1'; icon.setAttribute('data-lucide','locate-fixed'); if(window.lucide) lucide.createIcons(); }, 2000);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}
</script>
@endpush
