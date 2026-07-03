@extends('emails.layout')
@php
  $headerTitle    = "Asset Assigned to You 🖥️";
  $headerSubtitle = "A company asset has been assigned to your account";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View Asset Details";
@endphp
@section('body')

<p>Hi <strong>{{ $assignment->employee->first_name }}</strong>,</p>
<p>A company asset has been assigned to you. Please handle it with care and report any damage or issues promptly.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Asset Information</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Asset Name',   $assignment->asset->name ?? '-'],
          ['Asset Code',   $assignment->asset->asset_code ?? $assignment->asset->code ?? '-'],
          ['Category',     $assignment->asset->category?->name ?? '-'],
          ['Brand/Model',  trim(($assignment->asset->brand ?? '') . ' ' . ($assignment->asset->model ?? '')) ?: '-'],
          ['Serial No.',   $assignment->asset->serial_number ?? '-'],
          ['Assigned On',  isset($assignment->assigned_date) ? $assignment->assigned_date->format('D, M j, Y') : now()->format('D, M j, Y')],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:130px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  @if(!empty($assignment->notes))
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:#fff; border-radius:8px; border:1px solid #E5E7EB; padding:14px 16px;">
        <div style="font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Notes</div>
        <div style="font-size:14px; color:#374151; line-height:1.6;">{{ $assignment->notes }}</div>
      </div>
    </td>
  </tr>
  @endif
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF7ED; border-radius:8px; border-left:4px solid #F59E0B; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <span style="font-size:13px; color:#92400E;">⚠️ <strong>Reminder:</strong> This asset remains company property. You are responsible for its safekeeping. Return it promptly when requested by HR.</span>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
