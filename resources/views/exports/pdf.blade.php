<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }
    .header { background: #4F46E5; color: #fff; padding: 18px 24px; margin-bottom: 20px; }
    .header h1 { font-size: 18px; font-weight: bold; letter-spacing: 0.5px; }
    .header p { font-size: 10px; opacity: 0.8; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #f3f4f6; }
    thead th { padding: 8px 10px; text-align: left; font-size: 10px; font-weight: bold;
               text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280;
               border-bottom: 2px solid #e5e7eb; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; }
    .footer { margin-top: 16px; font-size: 9px; color: #9ca3af; text-align: right; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-green  { background: #d1fae5; color: #065f46; }
    .badge-red    { background: #fee2e2; color: #991b1b; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-blue   { background: #dbeafe; color: #1e40af; }
    .badge-gray   { background: #f3f4f6; color: #374151; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $title }}</h1>
    <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
</div>

<table>
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
                    <td>{!! $cell !!}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($columns) }}" style="text-align:center;padding:20px;color:#9ca3af;">
                    No records found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Total records: {{ $rows->count() }} &nbsp;|&nbsp; Lockmytimes &copy; {{ date('Y') }}
</div>

</body>
</html>
