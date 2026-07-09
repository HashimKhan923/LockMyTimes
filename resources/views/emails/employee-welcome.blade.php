@extends('emails.layout')
@php
  $headerTitle = "Welcome aboard, {$employee->first_name}! ";
  $headerSubtitle = "Your employee account has been created";
  $ctaUrl         = $loginUrl;
  $ctaText        = "Log In to Your Account";
@endphp
@section('body')

<p style="font-size:15px; color:#374151;">
  Hi <strong>{{ $employee->first_name }}</strong>,
</p>
<p style="color:#374151;">
  Welcome to <strong>{{ $companyName }}</strong>! We're excited to have you on the team.
  Your HR portal account has been set up and is ready for you to use.
</p>

{{-- Credentials Box --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #EEF2FF, #F5F3FF); border-radius: 12px; padding: 0; margin: 24px 0; border: 1px solid #C7D2FE;">
  <tr>
    <td style="padding: 24px 28px;">
      <div style="font-size:12px; font-weight:700; color:#4F46E5; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px;">Your Login Credentials</div>
      <table cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding: 6px 0; width: 90px;">
            <span style="font-size:13px; color:#9CA3AF; font-weight:500;">Email</span>
          </td>
          <td style="padding: 6px 0;">
            <span style="font-size:14px; color:#1E1B4B; font-weight:600; font-family: monospace;">{{ $employee->email }}</span>
          </td>
        </tr>
        <tr>
          <td style="padding: 6px 0;">
            <span style="font-size:13px; color:#9CA3AF; font-weight:500;">Password</span>
          </td>
          <td style="padding: 6px 0;">
            <span style="font-size:14px; color:#1E1B4B; font-weight:600; font-family: monospace; background:#fff; padding: 3px 10px; border-radius:6px; border: 1px solid #E0E7FF;">{{ $tempPassword }}</span>
          </td>
        </tr>
        <tr>
          <td style="padding: 6px 0;">
            <span style="font-size:13px; color:#9CA3AF; font-weight:500;">Portal</span>
          </td>
          <td style="padding: 6px 0;">
            <a href="{{ $loginUrl }}" style="font-size:13px; color:#4F46E5;">{{ $loginUrl }}</a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

{{-- Security notice --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF7ED; border-radius:8px; border-left: 4px solid #F59E0B; margin-bottom:20px;">
  <tr>
    <td style="padding: 14px 18px;">
      <span style="font-size:13px; color:#92400E;">️ &nbsp;<strong>Security tip:</strong> You will be asked to change your password upon first login. Please keep your credentials confidential.</span>
    </td>
  </tr>
</table>

@if($employee->department || $employee->position)
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
  <tr>
    @if($employee->department)
    <td style="padding: 12px 16px; background:#F9FAFB; border-radius:8px; width:48%; margin-right:4%;">
      <div style="font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px;">Department</div>
      <div style="font-size:14px; font-weight:600; color:#111827; margin-top:2px;">{{ $employee->department->name }}</div>
    </td>
    @endif
    @if($employee->position)
    <td style="width:4%;"></td>
    <td style="padding: 12px 16px; background:#F9FAFB; border-radius:8px; width:48%;">
      <div style="font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px;">Position</div>
      <div style="font-size:14px; font-weight:600; color:#111827; margin-top:2px;">{{ $employee->position->title }}</div>
    </td>
    @endif
  </tr>
</table>
@endif

<p style="color:#6B7280; font-size:14px;">
  If you have any questions, please reach out to your HR team. We're here to help!
</p>
<p style="color:#374151; font-size:14px;">
  Warm regards,<br/>
  <strong>{{ $companyName }} HR Team</strong>
</p>

@endsection
