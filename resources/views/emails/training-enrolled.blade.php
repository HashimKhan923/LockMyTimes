@extends('emails.layout')
@php
  $headerTitle    = "You've Been Enrolled in Training 🎓";
  $headerSubtitle = "A new training session has been assigned to you";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View Training Details";
@endphp
@section('body')

<p>Hi <strong>{{ $employee->first_name }}</strong>,</p>
<p>You have been enrolled in an upcoming training program. Here are the details:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Training Details</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Program',     $training->title],
          ['Category',    $training->category ?? '-'],
          ['Start Date',  isset($training->start_date) ? $training->start_date->format('D, M j, Y') : '-'],
          ['End Date',    isset($training->end_date) ? $training->end_date->format('D, M j, Y') : '-'],
          ['Trainer',     $training->trainer ?? '-'],
          ['Location',    $training->location ?? 'Online / TBD'],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:110px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  @if(!empty($training->description))
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:#fff; border-radius:8px; border:1px solid #E5E7EB; padding:14px 16px;">
        <div style="font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Description</div>
        <div style="font-size:14px; color:#374151; line-height:1.6;">{{ $training->description }}</div>
      </div>
    </td>
  </tr>
  @endif
</table>

<p style="color:#374151; font-size:14px;">Please ensure you attend this training as scheduled. If you have any conflicts, contact HR immediately.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
