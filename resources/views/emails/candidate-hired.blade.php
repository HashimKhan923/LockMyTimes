@extends('emails.layout')
@php
  $headerTitle = "Congratulations — You're Hired! ";
  $headerSubtitle = "Welcome to the " . $companyName . " family!";
  $ctaUrl         = null;
  $ctaText        = null;
@endphp
@section('body')

<p>Dear <strong>{{ $candidate->name ?? ($candidate->first_name . ' ' . $candidate->last_name) }}</strong>,</p>
<p>We are thrilled to inform you that you have been <strong style="color:#059669;">selected</strong> for the following position at <strong>{{ $companyName }}</strong>. Welcome aboard!</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F0FDF4; border-radius:12px; border:1px solid #A7F3D0; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#059669,#10B981); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;"> Offer Details</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Position',   $candidate->jobPosting->title ?? '-'],
          ['Department', $candidate->jobPosting->department->name ?? '-'],
          ['Type',       $candidate->jobPosting->employment_type ?? '-'],
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

<table width="100%" cellpadding="0" cellspacing="0" style="background:#EEF2FF; border-radius:8px; border-left:4px solid #6D28D9; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <span style="font-size:13px; color:#3730A3;"> <strong>Next steps:</strong> Our HR team will contact you shortly with the formal offer letter, joining date, and onboarding information. Please keep an eye on your inbox.</span>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">We look forward to having you on our team. If you have any questions in the meantime, please don't hesitate to reach out.</p>
<p style="color:#374151; font-size:14px;">Warm regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
