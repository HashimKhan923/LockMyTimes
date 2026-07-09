@extends('emails.layout')
@php
  $headerTitle = "Application Received ️";
  $headerSubtitle = "Thank you for applying at " . $companyName;
  $ctaUrl         = null;
  $ctaText        = null;
@endphp
@section('body')

<p>Dear <strong>{{ $candidate->name ?? ($candidate->first_name . ' ' . $candidate->last_name) }}</strong>,</p>
<p>Thank you for applying to <strong>{{ $companyName }}</strong>. We have received your application and our team will review it shortly.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Application Summary</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Position',     $candidate->jobPosting->title ?? '-'],
          ['Department',   $candidate->jobPosting->department->name ?? '-'],
          ['Applied On',   now()->format('D, M j, Y')],
          ['Reference No.','APP-' . str_pad($candidate->id, 6, '0', STR_PAD_LEFT)],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:140px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F0FDF4; border-radius:8px; border-left:4px solid #10B981; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <span style="font-size:13px; color:#065F46;"> <strong>What happens next?</strong> Our HR team will review your application and reach out if you are shortlisted for an interview.</span>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">If you have questions about your application, please reply to this email. We typically respond within 3–5 business days.</p>
<p style="color:#374151; font-size:14px;">Good luck!<br/>Regards,<br/><strong>{{ $companyName }} Recruitment Team</strong></p>

@endsection
