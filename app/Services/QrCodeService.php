<?php

namespace App\Services;

use App\Models\Tenant\QrCode;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generate a QR code SVG string for the given token.
     */
    public function generate(string $token, int $size = 300): string
    {
        $url = $this->buildClockInUrl($token);
        return $this->renderSvg($url, $size);
    }

    /**
     * Generate a base64 data URI for embedding in <img src="...">.
     */
    public function generateDataUri(string $token, int $size = 300): string
    {
        $svg = $this->generate($token, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Build the clock-in deep link URL baked into the QR code.
     */
    public function buildClockInUrl(string $token): string
    {
        return url("/api/v1/attendance/scan/{$token}");
    }

    /**
     * Generate a fresh unique token.
     */
    public function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (QrCode::where('token', $token)->exists());

        return $token;
    }

    /**
     * Rotate the token on an existing QR code record.
     */
    public function rotateToken(QrCode $qrCode): QrCode
    {
        $qrCode->update([
            'token'           => $this->generateToken(),
            'last_rotated_at' => now(),
        ]);
        return $qrCode->fresh();
    }

    /**
     * Render an SVG QR code using bacon/bacon-qr-code.
     */
    protected function renderSvg(string $data, int $size): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($data);
    }
}