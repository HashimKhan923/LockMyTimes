<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip {{ $payslip->payslip_number }}</title>
    <style>
        @page { margin: 16mm 14mm; }
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

        .doc { border: 1px solid #e5e7eb; border-radius: 8px; padding: 22px 24px; }

        /* Letterhead */
        .letterhead table { width: 100%; border-collapse: collapse; }
        .letterhead td { vertical-align: top; padding: 0; }
        .brand { font-size: 15pt; font-weight: 800; color: #111827; }
        .doctype { font-size: 8pt; font-weight: 700; color: #6C7DF7; text-transform: uppercase; letter-spacing: 1px; margin-top: 3px; }
        .num { font-size: 12pt; font-weight: 800; text-align: right; color: #111827; }
        .paydate { font-size: 8.5pt; color: #6b7280; text-align: right; margin-top: 2px; }
        .status { display: inline-block; margin-top: 6px; padding: 3px 11px; border-radius: 20px; background: #f3f4f6; color: #374151; font-size: 7.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; }
        .status.paid { background: #d1fae5; color: #065f46; }
        .status.cancelled { background: #fee2e2; color: #991b1b; }

        .rule { height: 3px; border-radius: 2px; margin: 16px 0 18px; background: #6C7DF7; }

        /* Employee + Period */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px dashed #e5e7eb; }
        .info-table td { vertical-align: top; width: 50%; padding: 0 12px; }
        .info-table td:first-child { padding-left: 0; }
        .info-table td:last-child { padding-right: 0; }
        .info-label { font-size: 7.5pt; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 8px; }
        .emp-name { font-size: 11pt; font-weight: 800; color: #111827; }
        .emp-meta { font-size: 9pt; color: #6b7280; margin-top: 1px; }
        .info-row { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .info-row td { padding: 3px 0; font-size: 9pt; width: auto; }
        .info-row td.k { color: #9ca3af; }
        .info-row td.v { text-align: right; color: #111827; font-weight: 600; }

        /* Earnings | Deductions columns */
        .columns { width: 100%; border-collapse: collapse; border: 1px solid #f1f5f9; border-radius: 8px; margin-bottom: 16px; }
        .columns > tr > td { vertical-align: top; width: 50%; padding: 12px 16px; }
        .columns > tr > td:first-child { border-right: 1px solid #f1f5f9; }
        .col-head { font-size: 8pt; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; padding-bottom: 8px; margin-bottom: 8px; border-bottom: 2px solid; }
        .col-head.earn { color: #059669; border-color: #d1fae5; }
        .col-head.ded { color: #dc2626; border-color: #fee2e2; }
        .line { width: 100%; border-collapse: collapse; }
        .line td { padding: 3.5px 0; font-size: 9pt; }
        .line td.amount { text-align: right; font-weight: 600; white-space: nowrap; }
        .line tr.total td { border-top: 1px solid #e5e7eb; padding-top: 6px; margin-top: 3px; font-weight: 800; font-size: 9.5pt; }
        .ded-amount { color: #dc2626; }

        .reimburse { background: #eff6ff; color: #1d4ed8; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 9pt; }
        .reimburse-tag { font-size: 6.5pt; font-weight: 700; text-transform: uppercase; color: #60a5fa; }

        .netpay { background: #059669; border-radius: 10px; padding: 14px 20px; margin-bottom: 16px; }
        .netpay table { width: 100%; border-collapse: collapse; }
        .netpay td { vertical-align: middle; }
        .net-label { font-size: 9.5pt; font-weight: 700; color: rgba(255,255,255,.85); }
        .net-sub { font-size: 7.5pt; color: rgba(255,255,255,.6); margin-top: 2px; }
        .net-amount { text-align: right; font-size: 19pt; font-weight: 800; color: #fff; }

        .grid-stats { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin-bottom: 4px; }
        .grid-stats td { background: #f9fafb; border-radius: 6px; padding: 7px 4px; text-align: center; width: 16.66%; }
        .grid-stats .v { font-size: 10.5pt; font-weight: 800; color: #111827; }
        .grid-stats .l { font-size: 6pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .4px; margin-top: 1px; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 7.5pt; color: #9ca3af; line-height: 1.6; }
    </style>
</head>
<body>
<div class="doc">

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
        $earnItems = $payslip->items->where('type', 'earning');
        $dedItems  = $payslip->items->whereIn('type', ['deduction', 'tax']);
        $reimburse = $payslip->items->where('type', 'reimbursement');

        $statusClass = $payslip->status === 'paid' ? 'paid'
                     : ($payslip->status === 'cancelled' ? 'cancelled' : '');
        $statusLabel = $payslip->status === 'finalized' ? 'Issued' : ucfirst($payslip->status);
    @endphp

    {{-- ════════ LETTERHEAD ════════ --}}
    <div class="letterhead">
        <table>
            <tr>
                <td>
                    @if($companyLogo)
                    <div style="display:inline-block; background:#ffffff; border:1px solid #f1f5f9; border-radius:8px; padding:8px 12px; margin-bottom:6px;">
                        <img src="{{ $companyLogo }}" style="height:30px; max-width:150px; object-fit:contain; display:block;" alt="{{ $companyName }}"/>
                    </div>
                    @else
                    <div class="brand">{{ $companyName }}</div>
                    @endif
                    <div class="doctype">Pay Statement</div>
                </td>
                <td>
                    <div class="num">{{ $payslip->payslip_number }}</div>
                    <div class="paydate">Pay date {{ $payslip->pay_date?->format('F j, Y') ?? '—' }}</div>
                    <div style="text-align:right;">
                        <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="rule"></div>

    {{-- ════════ EMPLOYEE + PERIOD ════════ --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="info-label">Paid To</div>
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

    {{-- ════════ EARNINGS | DEDUCTIONS ════════ --}}
    <table class="columns">
        <tr>
            <td>
                <div class="col-head earn">Earnings</div>
                <table class="line">
                    @forelse($earnItems as $item)
                        <tr>
                            <td>{{ $item->label }}</td>
                            <td class="amount">{{ $sym }}{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="color:#9ca3af; font-style:italic;">No earnings.</td></tr>
                    @endforelse
                    <tr class="total">
                        <td>Gross Pay</td>
                        <td class="amount">{{ $sym }}{{ number_format($payslip->gross_pay, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="col-head ded">Deductions</div>
                <table class="line">
                    @forelse($dedItems as $item)
                        <tr>
                            <td>{{ $item->label }}</td>
                            <td class="amount ded-amount">-{{ $sym }}{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="color:#9ca3af; font-style:italic;">No deductions.</td></tr>
                    @endforelse
                    <tr class="total">
                        <td>Total Deductions</td>
                        <td class="amount ded-amount">-{{ $sym }}{{ number_format($payslip->total_deductions, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($reimburse->isNotEmpty())
    <div class="reimburse">
        @foreach($reimburse as $item)
        <table style="width:100%;"><tr>
            <td>{{ $item->label }} <span class="reimburse-tag">NON-TAXABLE REIMBURSEMENT</span></td>
            <td style="text-align:right; font-weight:700;">+{{ $sym }}{{ number_format($item->amount, 2) }}</td>
        </tr></table>
        @endforeach
    </div>
    @endif

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

    {{-- ════════ SUMMARY ════════ --}}
    <table class="grid-stats">
        <tr>
            <td>
                <div class="v">{{ $sym }}{{ number_format($payslip->ytd_gross, 0) }}</div>
                <div class="l">YTD Gross</div>
            </td>
            <td>
                <div class="v" style="color:#dc2626;">{{ $sym }}{{ number_format($payslip->ytd_taxes, 0) }}</div>
                <div class="l">YTD Taxes</div>
            </td>
            <td>
                <div class="v" style="color:#059669;">{{ $sym }}{{ number_format($payslip->ytd_net, 0) }}</div>
                <div class="l">YTD Net</div>
            </td>
            <td>
                <div class="v">{{ number_format($payslip->regular_hours, 1) }}</div>
                <div class="l">Regular Hrs</div>
            </td>
            <td>
                <div class="v" style="color:#6C7DF7;">{{ number_format($payslip->overtime_hours, 1) }}</div>
                <div class="l">Overtime Hrs</div>
            </td>
            <td>
                <div class="v" style="color:#7c3aed;">{{ number_format($payslip->leave_hours, 1) }}</div>
                <div class="l">Leave Hrs</div>
            </td>
        </tr>
    </table>

    {{-- ════════ FOOTER ════════ --}}
    <div class="footer">
        This is a computer-generated pay statement and requires no signature.
        <div>Issued by {{ $companyName }} on {{ now()->format('F j, Y') }}</div>
    </div>

</div>
</body>
</html>
