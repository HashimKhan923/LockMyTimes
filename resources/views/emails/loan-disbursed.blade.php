@extends('emails.layout')
@php
  $headerTitle = "Loan Disbursed ";
  $headerSubtitle = "Your loan amount has been released";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View Loan Schedule";
@endphp
@section('body')

<p>Hi <strong>{{ $loan->employee->first_name }}</strong>,</p>
<p>Your approved loan has been <strong style="color:#4F46E5;">disbursed</strong>. The funds have been processed.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Disbursement Details</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Loan Type',       $loan->loanType->name ?? '-'],
          ['Disbursed On',    now()->format('D, M j, Y')],
          ['Monthly EMI',     '$' . number_format($loan->emi_amount ?? 0, 2)],
          ['Total Months',    ($loan->tenure_months ?? '-') . ' months'],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:140px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:10px; padding:16px 20px; text-align:center;">
        <div style="font-size:12px; color:rgba(255,255,255,0.75); text-transform:uppercase; letter-spacing:1px;">Amount Disbursed</div>
        <div style="font-size:32px; font-weight:800; color:#fff; margin-top:4px;">${{ number_format($loan->principal_amount, 2) }}</div>
      </div>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">Repayments will begin from next month's salary. View the full repayment schedule in your portal.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
