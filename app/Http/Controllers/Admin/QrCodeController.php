<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Location;
use App\Models\Tenant\QrCode;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function __construct(protected QrCodeService $qrService) {}

    public function index(string $tenant)
    {
        $qrCodes   = QrCode::with('location')->latest()->get();
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        return view('admin.qrcodes.index', compact('qrCodes', 'locations', 'tenant'));
    }

    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'location_id'    => 'required|exists:locations,id',
            'label'          => 'nullable|string|max:100',
            'require_selfie' => 'boolean',
            'rotate_token'   => 'boolean',
        ]);

        QrCode::create([
            'location_id'    => $data['location_id'],
            'label'          => $data['label'] ?? null,
            'token'          => $this->qrService->generateToken(),
            'require_selfie' => $request->boolean('require_selfie'),
            'rotate_token'   => $request->boolean('rotate_token'),
            'is_active'      => true,
        ]);

        return back()->with('success', 'QR code generated successfully.');
    }

    public function show(string $tenant, QrCode $qrCode)
    {
        $qrCode->load('location');
        $svgOrUrl = $this->qrService->generateDataUri($qrCode->token, 350);
        return view('admin.qrcodes.show', compact('qrCode', 'svgOrUrl', 'tenant'));
    }

    public function rotate(string $tenant, QrCode $qrCode)
    {
        $this->qrService->rotateToken($qrCode);
        return back()->with('success', 'QR code token rotated. Print and replace the old QR.');
    }

    public function toggle(string $tenant, QrCode $qrCode)
    {
        $qrCode->update(['is_active' => ! $qrCode->is_active]);
        return back()->with('success', 'QR code '.($qrCode->is_active ? 'activated' : 'deactivated').'.');
    }

    public function destroy(string $tenant, QrCode $qrCode)
    {
        $qrCode->delete();
        return back()->with('success', 'QR code deleted.');
    }
}