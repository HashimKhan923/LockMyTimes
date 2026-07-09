@extends('emails.layout')
@php
  $headerTitle    = $announcement->title;
  $headerSubtitle = "Official announcement from " . $companyName;
  $ctaUrl         = $portalUrl;
  $ctaText        = "View in Portal";
@endphp
@section('body')

<p>Dear Team,</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;"> {{ $announcement->title }}</span>
    </td>
  </tr>
  @if($announcement->category ?? false)
  <tr>
    <td style="padding:14px 24px 0; font-size:12px; color:#9CA3AF;">
      Category: <strong>{{ $announcement->category }}</strong>
      &nbsp;·&nbsp; {{ isset($announcement->published_at) ? $announcement->published_at->format('M j, Y') : now()->format('M j, Y') }}
    </td>
  </tr>
  @endif
  <tr>
    <td style="padding:20px 24px; font-size:15px; color:#374151; line-height:1.7;">
      {!! nl2br(e($announcement->content)) !!}
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">Please log into the portal for any attachments or additional information related to this announcement.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} Management</strong></p>

@endsection
