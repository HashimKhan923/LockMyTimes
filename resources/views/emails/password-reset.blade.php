@extends('emails.layout')
@php
  $headerTitle    = "Reset your password";
  $headerSubtitle = "We received a request to reset your password";
  $ctaUrl         = $resetUrl;
  $ctaText        = "Reset Password";
@endphp
@section('body')

<p style="font-size:15px; color:#374151;">
  Hi <strong>{{ $user->name }}</strong>,
</p>
<p style="color:#374151;">
  We received a request to reset the password for your <strong>{{ $companyName }}</strong> account.
  Use the code below — on the web or in the app — or click the button to open the reset page directly.
  This code expires in {{ $expiresInMinutes }} minutes.
</p>

{{-- 6-digit code --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #EEF2FF, #F5F3FF); border-radius: 12px; padding: 0; margin: 24px 0; border: 1px solid #C7D2FE;">
  <tr>
    <td style="padding: 24px; text-align:center;">
      <div style="font-size:12px; font-weight:700; color:#4F46E5; text-transform:uppercase; letter-spacing:1px; margin-bottom:12px;">Your reset code</div>
      <div style="font-size:36px; font-weight:800; color:#1E1B4B; letter-spacing:10px; font-family: monospace;">{{ $token }}</div>
    </td>
  </tr>
</table>

{{-- Security notice --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF7ED; border-radius:8px; border-left: 4px solid #F59E0B; margin-bottom:20px;">
  <tr>
    <td style="padding: 14px 18px;">
      <span style="font-size:13px; color:#92400E;">&nbsp;<strong>Didn't request this?</strong> If you didn't ask to reset your password, you can safely ignore this email — your password will stay unchanged.</span>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">
  Warm regards,<br/>
  <strong>{{ $companyName }} HR Team</strong>
</p>

@endsection
