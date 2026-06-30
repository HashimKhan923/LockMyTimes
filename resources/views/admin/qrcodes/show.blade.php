@extends('layouts.admin')
@section('title','QR Code — '.$qrCode->label ?? $qrCode->location->name)
@section('page-title','QR Code')

@section('content')

<div class="max-w-md mx-auto">
    <a href="{{ route('admin.qrcodes.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to QR Codes
    </a>

    {{-- Printable QR card --}}
    <div class="lmt-card text-center" id="print-card">
        <div class="lmt-gradient-bg rounded-xl p-4 mb-5 mx-auto w-fit">
            <div class="flex items-center gap-2 text-white">
                <i data-lucide="clock" class="w-5 h-5"></i>
                <span class="font-black text-lg">Lockmytimes</span>
            </div>
        </div>

        <h2 class="text-2xl font-black text-gray-900 mb-1">
            {{ $qrCode->label ?? $qrCode->location->name }}
        </h2>
        <p class="text-sm text-gray-500 mb-6">Scan to Clock In / Clock Out</p>

        {{-- QR Code --}}
        <div class="flex justify-center mb-6">
            <div class="p-4 bg-white border-4 border-gray-900 rounded-2xl shadow-lg">
                {!! app(\App\Services\QrCodeService::class)->generate($qrCode->token, 300) !!}
            </div>
        </div>

        {{-- Location info --}}
        <div class="bg-gray-50 rounded-xl p-4 mb-5 text-left">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Location</p>
                    <p class="font-semibold text-gray-900">{{ $qrCode->location->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Geo-fence</p>
                    <p class="font-semibold text-gray-900">{{ $qrCode->location->geofence_radius_meters }}m radius</p>
                </div>
                @if($qrCode->location->address_line1)
                <div class="col-span-2">
                    <p class="text-xs text-gray-400">Address</p>
                    <p class="font-semibold text-gray-900">
                        {{ $qrCode->location->address_line1 }}, {{ $qrCode->location->city }}
                    </p>
                </div>
                @endif
            </div>
        </div>

        @if($qrCode->require_selfie)
        <div class="lmt-alert lmt-alert-warning mb-4 text-xs">
            <i data-lucide="camera" class="w-4 h-4 shrink-0"></i>
            Selfie capture is required for this location
        </div>
        @endif

        <p class="text-xs text-gray-400">
            Token ID: <code>{{ substr($qrCode->token, 0, 12) }}…</code>
        </p>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 mt-4">
        <button onclick="window.print()"
                class="lmt-btn-primary flex-1">
            <i data-lucide="printer" class="w-4 h-4"></i>
            Print QR Code
        </button>
        <form action="{{ route('admin.qrcodes.rotate', [$tenant, $qrCode->id]) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-secondary"
                    onclick="return confirm('This will invalidate the current QR. Continue?')">
                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                Rotate
            </button>
        </form>
    </div>
</div>

@endsection

@push('head')
<style>
@media print {
    .adm-sidebar, header, .adm-content > a, .flex.items-center.gap-3.mt-4 { display: none !important; }
    .adm-content { padding: 0 !important; }
    #print-card { box-shadow: none !important; border: none !important; }
    body { background: white !important; }
}
</style>
@endpush
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush