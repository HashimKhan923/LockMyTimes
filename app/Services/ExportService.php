<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ExportService
{
    /**
     * Return a PDF download response using a generic table template. Carries the tenant's
     * own branding (company name + logo) into the letterhead, same as outbound email —
     * see MailService::ctx() for the identical pattern.
     */
    public function pdf(string $title, array $columns, Collection|array $rows, string $filename, string $orientation = 'portrait'): Response
    {
        $tenant = TenantManager::current();

        $pdf = Pdf::loadView('exports.pdf', [
            'title'       => $title,
            'columns'     => $columns,
            'rows'        => collect($rows),
            'companyName' => $tenant?->company_name ?? 'Your Company',
            'companyLogo' => $tenant?->logo ? asset('storage/' . $tenant->logo) : null,
        ])->setPaper('a4', $orientation);

        return $pdf->download($filename);
    }

    /**
     * Return an Excel-compatible CSV download response.
     * Includes UTF-8 BOM so Excel opens it correctly with proper encoding.
     */
    public function excel(array $headers, Collection|array $rows, string $filename): Response
    {
        $bom = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $csv = $bom . implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";

        foreach (collect($rows) as $row) {
            $csv .= implode(',', array_map(function ($cell) {
                $cell = (string) ($cell ?? '');
                return '"' . str_replace('"', '""', $cell) . '"';
            }, (array) $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
