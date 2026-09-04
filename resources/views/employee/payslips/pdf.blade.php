<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip {{ $payslip->payslip_number }}</title>
    <style>
        @page { margin: 18mm 14mm 18mm 14mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        h1, h2, h3, h4 { margin: 0; padding: 0; font-weight: 700; }

        .ps-header {
            background: #6C7DF7;
            color: #ffffff;
            padding: 16px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
        }
        .ps-header table { width: 100%; border-collapse: collapse; }
        .ps-header td { vertical-align: top; padding: 0; }
        .ps-header .brand {
            font-size: 16pt;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .ps-header .label {
            font-size: 8pt;
            opacity: 0.75;
            margin-top: 2px;
        }
        .ps-header .num {
            font-size: 14pt;
            font-weight: 800;
            text-align: right;
        }
        .ps-header .date {
            font-size: 9pt;
            opacity: 0.8;
            text-align: right;
            margin-top: 2px;
        }
        .ps-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(255,255,255,0.25);
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        .ps-status.paid { background: #10b981; }
        .ps-status.cancelled { background: #ef4444; }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
            padding: 0 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 14px;
        }
        .info-table td:first-child { padding-left: 0; border-right: 1px solid #f3f4f6; }
        .info-table td:last-child  { padding-right: 0; }

        .info-label {
            font-size: 7.5pt;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }
        .emp-name {
            font-size: 11pt;
            font-weight: 800;
            color: #111827;
        }
        .emp-meta {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 1px;
        }

        .info-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .info-row td {
            padding: 3px 0;
            font-size: 9pt;
            border: none !important;
        }
        .info-row td.k { color: #9ca3af; }
        .info-row td.v { text-align: right; color: #111827; font-weight: 600; }

        .section {
            margin-top: 16px;
        }
        .section-head {
            font-size: 8pt;
            font-weight: 800;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #f3f4f6;
        }
        .line {
            width: 100%;
            border-collapse: collapse;
        }
        .line td {
            padding: 5px 0;
            font-size: 9.5pt;
        }
        .line td.amount { text-align: right; font-weight: 600; }
        .line tr.total td {
            border-top: 1px solid #e5e7eb;
            padding-top: 7px;
            margin-top: 4px;
            font-weight: 800;
            font-size: 10pt;
        }
        .ded-amount { color: #ef4444; font-weight: 600; }

        .netpay {
            margin-top: 16px;
            background: #ecfdf5;
            border-radius: 6px;
            padding: 14px 18px;
        }
        .netpay table { width: 100%; border-collapse: collapse; }
        .netpay td { vertical-align: middle; }
        .netpay .net-label {
            font-size: 10pt;
            font-weight: 700;
            color: #065f46;
        }
        .netpay .net-sub {
            font-size: 8pt;
            color: #047857;
            margin-top: 2px;
        }
        .netpay .net-amount {
            text-align: right;
            font-size: 20pt;
            font-weight: 800;
            color: #065f46;
        }

        .grid-stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-top: 14px;
        }
        .grid-stats td {
            background: #f9fafb;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
            width: 33.33%;
        }
        .grid-stats .v {
            font-size: 13pt;
            font-weight: 800;
            color: #111827;
        }
        .grid-stats .v.red { color: #ef4444; }
        .grid-stats .v.green { color: #10b981; }
        .grid-stats .l {
            font-size: 7pt;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 2px;
        }

        .grid-hours {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-top: 6px;
        }
        .grid-hours td {
            background: #f9fafb;
            border-radius: 6px;
            padding: 8px 6px;
            text-align: center;
            width: 25%;
        }
        .grid-hours .v {
            font-size: 12pt;
            font-weight: 800;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #f3f4f6;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
        }
        .footer .gen {
            font-size: 7.5pt;
            color: #d1d5db;
            margin-top: 3px;
        }
    </style>
</head>
<body>

    @php
        $sym = match($currency) {
            'USD', 'CAD', 'AUD', 'SGD', 'HKD', 'NZD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY', 'CNY' => '¥',
            'INR' => '₹',
            'PKR' => 'Rs ',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            default => $currency.' ',
        };

        // Itemized breakdown — the single source of truth (see employee.payslips.show for why
        // the old aggregate base_pay/bonus/etc columns aren't also rendered here: PayrollService
        // writes every one of those as its own item too, so showing both double-counts every line).
        $earnItems = $payslip->items->whereIn('type', ['earning', 'reimbursement']);
        $dedItems  = $payslip->items->whereIn('type', ['deduction', 'tax']);

        $statusClass = $payslip->status === 'paid' ? 'paid'
                     : ($payslip->status === 'cancelled' ? 'cancelled' : '');
        $statusLabel = $payslip->status === 'finalized' ? 'Issued' : ucfirst($payslip->status);
    @endphp

    {{-- ════════ HEADER ════════ --}}
    <div class="ps-header">
        <table>
            <tr>
                <td>
                    @if($companyLogo)
                    <div style="display:inline-block; background:#ffffff; border-radius:8px; padding:10px 14px; margin-bottom:8px;">
                        <img src="{{ $companyLogo }}" style="height:38px; max-width:180px; object-fit:contain; display:block;" alt="{{ $companyName }}"/>
                    </div>
                    @else
                    <div class="brand">{{ $companyName }}</div>
                    @endif
                    <div class="label">Pay Slip</div>
                </td>
                <td>
                    <div class="num">{{ $payslip->payslip_number }}</div>
                    <div class="date">{{ $payslip->pay_date?->format('F j, Y') ?? '—' }}</div>
                    <div style="text-align:right;">
                        <span class="ps-status {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ════════ EMPLOYEE + PERIOD ════════ --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="info-label">Employee</div>
                <div class="emp-name">{{ $payslip->employee->full_name }}</div>
                <div class="emp-meta">{{ $payslip->employee->employee_code }}</div>
                <div class="emp-meta" style="margin-top:6px;">{{ $payslip->employee->position?->title ?? '—' }}</div>
                <div class="emp-meta">{{ $payslip->employee->department?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="info-label">Pay Period</div>
                <table class="info-row">
                    <tr>
                        <td class="k">Period</td>
                        <td class="v">{{ $payslip->period_start->format('M j') }} – {{ $payslip->period_end->format('M j, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="k">Pay Date</td>
                        <td class="v">{{ $payslip->pay_date?->format('F j, Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Schedule</td>
                        <td class="v">{{ ucfirst(str_replace('_', ' ', $payslip->payrollRun->pay_schedule ?? 'monthly')) }}</td>
                    </tr>
                    <tr>
                        <td class="k">Payment</td>
                        <td class="v">{{ ucfirst(str_replace('_', ' ', $payslip->payment_method)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ════════ EARNINGS ════════ --}}
    <div class="section">
        <div class="section-head">Earnings</div>
        <table class="line">
            @forelse($earnItems as $item)
                <tr>
                    <td>{{ $item->label }}</td>
                    <td class="amount">{{ $sym }}{{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" style="color:#9ca3af; font-style:italic;">No earnings on this payslip.</td></tr>
            @endforelse
            <tr class="total">
                <td>Gross Pay</td>
                <td class="amount">{{ $sym }}{{ number_format($payslip->gross_pay, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- ════════ DEDUCTIONS ════════ --}}
    <div class="section">
        <div class="section-head">Deductions</div>
        <table class="line">
            @forelse($dedItems as $item)
                <tr>
                    <td>{{ $item->label }}</td>
                    <td class="amount ded-amount">−{{ $sym }}{{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" style="color:#9ca3af; font-style:italic;">No deductions on this payslip.</td></tr>
            @endforelse
            <tr class="total">
                <td>Total Deductions</td>
                <td class="amount ded-amount">−{{ $sym }}{{ number_format($payslip->total_deductions, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- ════════ NET PAY ════════ --}}
    <div class="netpay">
        <table>
            <tr>
                <td>
                    <div class="net-label">Net Pay</div>
                    <div class="net-sub">Take-home amount</div>
                </td>
                <td class="net-amount">
                    {{ $sym }}{{ number_format($payslip->net_pay, 2) }}
                </td>
            </tr>
        </table>
    </div>

    {{-- ════════ YTD ════════ --}}
    <table class="grid-stats">
        <tr>
            <td>
                <div class="v">{{ $sym }}{{ number_format($payslip->ytd_gross, 0) }}</div>
                <div class="l">YTD Gross</div>
            </td>
            <td>
                <div class="v red">{{ $sym }}{{ number_format($payslip->ytd_taxes, 0) }}</div>
                <div class="l">YTD Taxes</div>
            </td>
            <td>
                <div class="v green">{{ $sym }}{{ number_format($payslip->ytd_net, 0) }}</div>
                <div class="l">YTD Net</div>
            </td>
        </tr>
    </table>

    {{-- ════════ HOURS ════════ --}}
    <table class="grid-hours">
        <tr>
            <td>
                <div class="v">{{ number_format($payslip->regular_hours, 1) }}</div>
                <div class="l">Regular Hrs</div>
            </td>
            <td>
                <div class="v" style="color:#6C7DF7;">{{ number_format($payslip->overtime_hours, 1) }}</div>
                <div class="l">Overtime Hrs</div>
            </td>
            <td>
                <div class="v" style="color:#d97706;">{{ number_format($payslip->holiday_hours, 1) }}</div>
                <div class="l">Holiday Hrs</div>
            </td>
            <td>
                <div class="v" style="color:#7c3aed;">{{ number_format($payslip->leave_hours, 1) }}</div>
                <div class="l">Leave Hrs</div>
            </td>
        </tr>
    </table>

    {{-- ════════ FOOTER ════════ --}}
    <div class="footer">
        Computer-generated payslip · No signature required
        <div class="gen">Generated by {{ $companyName }} · {{ now()->format('M j, Y h:i A') }}</div>
    </div>

</body>
</html>