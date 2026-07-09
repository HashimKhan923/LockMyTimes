@extends('emails.layout')
@php
  $headerTitle = "Expense Approved ";
  $headerSubtitle = "Your expense claim has been approved";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View in Portal";
@endphp
@section('body')

<p>Hi <strong>{{ $expense->employee->first_name }}</strong>,</p>
<p>Great news! Your expense claim has been <strong style="color:#059669;">approved</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F0FDF4; border-radius:12px; border:1px solid #A7F3D0; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#059669,#10B981); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;"> Approved Expense</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Category',    $expense->category?->name ?? '-'],
          ['Date',        $expense->expense_date->format('D, M j, Y')],
          ['Description', $expense->description ?? '-'],
          ['Approved By', $expense->approver?->name ?? 'HR Team'],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:130px; font-size:13px; color:#6B7280; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:linear-gradient(135deg,#059669,#10B981); border-radius:10px; padding:16px 20px; text-align:center;">
        <div style="font-size:12px; color:rgba(255,255,255,0.75); text-transform:uppercase; letter-spacing:1px;">Approved Amount</div>
        <div style="font-size:28px; font-weight:800; color:#fff; margin-top:4px;">${{ number_format($expense->amount, 2) }}</div>
      </div>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">The reimbursement will be processed according to your company's payment schedule.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
