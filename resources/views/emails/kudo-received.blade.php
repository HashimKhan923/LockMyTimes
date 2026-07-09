@extends('emails.layout')
@php
  $headerTitle = "You Received a Kudos! ";
  $headerSubtitle = "Someone appreciates your great work";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View in Portal";
@endphp
@section('body')

<p>Hi <strong>{{ $kudo->toEmployee->first_name }}</strong>,</p>
<p>Great news — a colleague has recognized your hard work with a kudos!</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">
        @if($kudo->badge) {{ $kudo->badge }} &nbsp; @endif Recognition Received
      </span>
    </td>
  </tr>
  <tr>
    <td style="padding:24px;">
      <div style="text-align:center; margin-bottom:20px;">
        <div style="font-size:48px; line-height:1;"></div>
        <div style="font-size:13px; color:#9CA3AF; margin-top:6px; text-transform:uppercase; letter-spacing:1px;">
          From: <strong style="color:#4F46E5;">{{ $kudo->fromEmployee->full_name }}</strong>
        </div>
      </div>

      <div style="background:linear-gradient(135deg,#EEF2FF,#F5F3FF); border-radius:10px; padding:20px 24px; border:1px solid #C7D2FE; text-align:center;">
        <div style="font-size:16px; color:#374151; line-height:1.7; font-style:italic;">
          "{{ $kudo->message }}"
        </div>
      </div>

      @if($kudo->badge)
      <div style="text-align:center; margin-top:16px;">
        <span style="display:inline-block; background:linear-gradient(135deg,#4F46E5,#7C3AED); color:#fff; font-size:13px; font-weight:700; padding:6px 18px; border-radius:20px;">
          {{ $kudo->badge }}
        </span>
      </div>
      @endif
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px; text-align:center;">Keep up the amazing work — you make <strong>{{ $companyName }}</strong> a better place! </p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
