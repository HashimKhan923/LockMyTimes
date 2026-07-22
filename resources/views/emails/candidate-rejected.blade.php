@extends('emails.layout')
@php
  $headerTitle    = "Application Status Update";
  $headerSubtitle = "Thank you for your interest in " . $companyName;
  $ctaUrl         = null;
  $ctaText        = null;
@endphp
@section('body')

<p>Dear <strong>{{ $candidate->name ?? ($candidate->first_name . ' ' . $candidate->last_name) }}</strong>,</p>
<p>Thank you for taking the time to apply for the <strong>{{ $candidate->jobPosting->title ?? 'position' }}</strong> role at <strong>{{ $companyName }}</strong> and for your interest in joining our team.</p>

<p>After careful consideration, we regret to inform you that we will not be moving forward with your application at this time. This was a competitive process and we received many strong applications.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F9FAFB; border-radius:12px; border:1px solid #E5E7EB; margin:20px 0;">
  <tr>
    <td style="padding:20px 24px; text-align:center;">
      <div style="font-size:32px; margin-bottom:8px;"></div>
      <div style="font-size:15px; font-weight:600; color:#374151;">We appreciate your effort and time</div>
      <div style="font-size:13px; color:#9CA3AF; margin-top:6px; line-height:1.6;">We encourage you to apply for future openings that match your skills and experience.</div>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">We wish you all the best in your job search and future career endeavors. Please keep an eye on our careers page for future opportunities.</p>
<p style="color:#374151; font-size:14px;">Kind regards,<br/><strong>{{ $companyName }} Recruitment Team</strong></p>

@endsection
