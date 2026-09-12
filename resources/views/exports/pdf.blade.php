<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<style>
    @page { margin: 16mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1f2937; margin: 0; padding: 0; line-height: 1.4; }
    h1, h2, h3 { margin: 0; padding: 0; }

    /* Document card — mirrors the payslip PDF's bordered card for a consistent, designed feel
       instead of content floating loose on the page. */
    .doc { border: 1px solid #e5e7eb; border-radius: 8px; padding: 22px 24px; }

    /* Letterhead */
    .letterhead table { width: 100%; border-collapse: collapse; }
    .letterhead td { vertical-align: top; padding: 0; }
    .brand { font-size: 15pt; font-weight: 800; color: #111827; }
    .doctype { font-size: 8pt; font-weight: 700; color: #6C7DF7; text-transform: uppercase; letter-spacing: 1px; margin-top: 3px; }
    .report-title { font-size: 12pt; font-weight: 800; text-align: right; color: #111827; }
    .gen-date { font-size: 8.5pt; color: #6b7280; text-align: right; margin-top: 2px; }
    .rule { height: 3px; border-radius: 2px; margin: 14px 0 18px; background: #6C7DF7; }

    /* Summary strip */
    .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 16px; }
    .summary td { background: #f8f8fd; border-radius: 8px; padding: 9px 6px; text-align: center; }
    .summary .v { font-size: 13pt; font-weight: 800; color: #111827; }
    .summary .l { font-size: 6.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .4px; margin-top: 1px; }

    /* Table */
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead tr { background: #f0f0fb; }
    table.data thead th {
        padding: 9px 10px; text-align: left; font-size: 8pt; font-weight: 800;
        text-transform: uppercase; letter-spacing: .5px; color: #4338CA;
        border-bottom: 2px solid #d8d8f5;
    }
    table.data tbody tr:nth-child(even) { background: #fafafa; }
    table.data tbody td { padding: 7.5px 10px; border-bottom: 1px solid #f1f1f6; font-size: 9pt; color: #374151; }
    table.data tbody tr:last-child td { border-bottom: none; }

    .badge { display: inline-block; padding: 2.5px 9px; border-radius: 10px; font-size: 8pt; font-weight: 700; }
    .badge-green  { background: #d1fae5; color: #065f46; }
    .badge-red    { background: #fee2e2; color: #991b1b; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-blue   { background: #e0e7ff; color: #3730a3; }
    .badge-gray   { background: #f3f4f6; color: #374151; }

    .footer { margin-top: 18px; padding-top: 10px; border-top: 1px dashed #e5e7eb; font-size: 8pt; color: #9ca3af; }
    .footer table { width: 100%; }
    .footer td.right { text-align: right; }
</style>
</head>
<body>
<div class="doc">

    @php
        // Auto-badge single-word status-like cells so tabular reports don't read as a wall of
        // plain text — the on-screen admin tables already color these the same way; this just
        // carries that same visual language into the PDF without every controller needing to
        // hand-build HTML for each row.
        $badgeMap = [
            'present' => 'green', 'approved' => 'green', 'paid' => 'green', 'active' => 'green',
            'completed' => 'green', 'disbursed' => 'green', 'yes' => 'green',
            'absent' => 'red', 'rejected' => 'red', 'inactive' => 'red', 'cancelled' => 'red',
            'terminated' => 'red', 'no' => 'red',
            'late' => 'yellow', 'pending' => 'yellow', 'submitted' => 'yellow', 'draft' => 'yellow',
            'on leave' => 'blue', 'in progress' => 'blue', 'scheduled' => 'blue',
        ];
        $badgeify = function ($cell) use ($badgeMap) {
            $plain = trim(strip_tags((string) $cell));
            $color = $badgeMap[strtolower($plain)] ?? null;
            return $color ? '<span class="badge badge-'.$color.'">'.e($plain).'</span>' : $cell;
        };
    @endphp

    {{-- ════════ LETTERHEAD ════════ --}}
    <div class="letterhead">
        <table>
            <tr>
                <td>
                    @if($companyLogo)
                    <table style="border-collapse:collapse; margin-bottom:6px;">
                        <tr>
                            <td style="padding:0;">
                                <div style="display:inline-block; background:#ffffff; border:1px solid #f1f5f9; border-radius:8px; padding:8px;">
                                    <img src="{{ $companyLogo }}" style="height:48px; max-width:180px; object-fit:contain; display:block;" alt="{{ $companyName }}"/>
                                </div>
                            </td>
                            <td style="padding:0 0 0 12px; vertical-align:middle;">
                                <div class="brand">{{ $companyName }}</div>
                            </td>
                        </tr>
                    </table>
                    @else
                    <div class="brand">{{ $companyName }}</div>
                    @endif
                    <div class="doctype">Report</div>
                </td>
                <td>
                    <div class="report-title">{{ $title }}</div>
                    <div class="gen-date">Generated {{ now()->format('F j, Y \a\t g:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="rule"></div>

    {{-- ════════ SUMMARY STRIP ════════ --}}
    <table class="summary">
        <tr>
            <td style="width:50%;">
                <div class="v">{{ $rows->count() }}</div>
                <div class="l">{{ \Illuminate\Support\Str::plural('Record', $rows->count()) }}</div>
            </td>
            <td style="width:50%;">
                <div class="v">{{ count($columns) }}</div>
                <div class="l">{{ \Illuminate\Support\Str::plural('Column', count($columns)) }}</div>
            </td>
        </tr>
    </table>

    {{-- ════════ TABLE ════════ --}}
    <table class="data">
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach((array)$row as $cell)
                        <td>{!! $badgeify($cell) !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align:center;padding:24px;color:#9ca3af;">
                        No records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ════════ FOOTER ════════ --}}
    <div class="footer">
        <table>
            <tr>
                <td>This is a computer-generated report and requires no signature.</td>
                <td class="right">Generated by {{ $companyName }}</td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
