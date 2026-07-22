@extends('emails.layout')
@php
  $headerTitle = "Your Leave Has Been Approved ";
  $headerSubtitle = "Great news — enjoy your time off!";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View in Portal";
@endphp
@section('body')

<p>Hi <strong>{{ $leave->employee->first_name }}</strong>,</p>
<p>We're happy to let you know that your leave request has been <strong style="color:#059669;">approved</strong>. Here's a summary:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F0FDF4; border-radius:12px; border:1px solid #A7F3D0; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#059669,#10B981); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;"> Approved Leave</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Leave Type', $leave->leaveType->name ?? '-'],
          ['From',       $leave->start_date->format('D, M j, Y')],
          ['To',         $leave->end_date->format('D, M j, Y')],
          ['Duration',   ($leave->total_days ?? '-') . ' working day(s)'],
          ['Approved By', $leave->approver?->name ?? 'HR Team'],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:130px; font-size:13px; color:#6B7280; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
</table>

@if($leave->approver_comments)
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F9FAFB; border-radius:8px; border-left:4px solid #10B981; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <div style="font-size:12px; color:#9CA3AF; margin-bottom:4px;">Note from approver</div>
      <div style="font-size:14px; color:#374151;">{{ $leave->approver_comments }}</div>
    </td>
  </tr>
</table>
@endif

<p style="color:#374151;">Have a great time off! If you need anything before your leave, don't hesitate to contact HR.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
