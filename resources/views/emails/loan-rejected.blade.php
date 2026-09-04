@extends('emails.layout')
@php
  $headerTitle    = "Loan Request Update";
  $headerSubtitle = "We have an update on your loan application";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View in Portal";
@endphp
@section('body')

<p>Hi <strong>{{ $loan->employee->first_name }}</strong>,</p>
<p>We regret to inform you that your loan application has <strong style="color:#DC2626;">not been approved</strong> at this time.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF5F5; border-radius:12px; border:1px solid #FECACA; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#DC2626,#EF4444); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;"> Not Approved</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Loan Type',   $loan->loanType->name ?? '-'],
          ['Requested',   '$' . number_format($loan->principal_amount, 2)],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:130px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
</table>

@if(!empty($loan->rejection_reason))
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF5F5; border-radius:8px; border-left:4px solid #EF4444; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <div style="font-size:12px; color:#9CA3AF; margin-bottom:4px;">Reason</div>
      <div style="font-size:14px; color:#374151;">{{ $loan->rejection_reason }}</div>
    </td>
  </tr>
</table>
@endif

<p style="color:#374151; font-size:14px;">If you have questions, please speak with HR. You may re-apply after 90 days or when your circumstances change.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
