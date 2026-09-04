@extends('emails.layout')
@php
  $headerTitle = "Loan Request Approved ";
  $headerSubtitle = "Your loan application has been approved";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View Loan Details";
@endphp
@section('body')

<p>Hi <strong>{{ $loan->employee->first_name }}</strong>,</p>
<p>We're pleased to inform you that your loan request has been <strong style="color:#059669;">approved</strong>!</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F0FDF4; border-radius:12px; border:1px solid #A7F3D0; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#059669,#10B981); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;"> Loan Approved</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Loan Type',        $loan->loanType->name ?? '-'],
          ['Loan Amount',      '$' . number_format($loan->principal_amount, 2)],
          ['Monthly EMI',      '$' . number_format($loan->emi_amount ?? 0, 2)],
          ['Repayment Period', ($loan->tenure_months ?? '-') . ' months'],
          ['Interest Rate',    ($loan->interest_rate ?? '0') . '%'],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:150px; font-size:13px; color:#6B7280; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">The disbursement will be processed shortly. Monthly installments will be deducted from your salary automatically.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
