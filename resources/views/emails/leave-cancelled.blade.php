@extends('emails.layout')
@php
  $headerTitle    = "Leave Cancellation Notice";
  $headerSubtitle = "Your approved leave has been cancelled";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View My Leaves";
@endphp
@section('body')

<p>Hi <strong>{{ $leave->employee->first_name }}</strong>,</p>
<p>We are writing to let you know that the following leave has been <strong style="color:#DC2626;">cancelled</strong> by HR:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF5F5; border-radius:12px; border:1px solid #FECACA; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#DC2626,#EF4444); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Cancelled Leave Details</span>
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

<p style="color:#374151; font-size:14px;">Your leave balance for this period has been restored. If you have questions about this cancellation, please contact HR directly.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
