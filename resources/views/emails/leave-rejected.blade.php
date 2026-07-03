@extends('emails.layout')
@php
  $headerTitle    = "Leave Request Update";
  $headerSubtitle = "We have an update on your leave request";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View My Leave Requests";
@endphp
@section('body')

<p>Hi <strong>{{ $leave->employee->first_name }}</strong>,</p>
<p>Unfortunately, your leave request has <strong style="color:#DC2626;">not been approved</strong> at this time. Here are the details:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF5F5; border-radius:12px; border:1px solid #FECACA; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#DC2626,#EF4444); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">✕ Not Approved</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Leave Type', $leave->leaveType->name ?? '-'],
          ['Requested', $leave->start_date->format('M j') . ' – ' . $leave->end_date->format('M j, Y')],
          ['Duration',  ($leave->total_days ?? '-') . ' working day(s)'],
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

@if($leave->rejection_reason)
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF5F5; border-radius:8px; border-left:4px solid #EF4444; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <div style="font-size:12px; color:#9CA3AF; margin-bottom:4px;">Reason for rejection</div>
      <div style="font-size:14px; color:#374151;">{{ $leave->rejection_reason }}</div>
    </td>
  </tr>
</table>
@endif

<p style="color:#374151;">If you have questions about this decision, please speak with your manager or the HR team. You're welcome to submit a new request for different dates.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
