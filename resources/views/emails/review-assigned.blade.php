@extends('emails.layout')
@php
  $headerTitle    = "Performance Review Assigned 📊";
  $headerSubtitle = "A performance review has been scheduled for you";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View Review";
@endphp
@section('body')

<p>Hi <strong>{{ $review->employee->first_name }}</strong>,</p>
<p>A performance review has been assigned to you. Please be prepared for your evaluation session.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Review Details</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Review Type',   $review->type ?? $review->reviewType->name ?? '-'],
          ['Period',        ($review->period_start ?? '') && ($review->period_end ?? '') ? $review->period_start->format('M j') . ' – ' . $review->period_end->format('M j, Y') : '-'],
          ['Reviewer',      $review->reviewer?->name ?? '-'],
          ['Due Date',      isset($review->due_date) ? $review->due_date->format('D, M j, Y') : '-'],
        ] as [$label, $value])
        @if($value && $value !== '-')
        <tr>
          <td style="padding:7px 0; width:130px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endif
        @endforeach
      </table>
    </td>
  </tr>
  @if(!empty($review->notes) || !empty($review->description))
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:#fff; border-radius:8px; border:1px solid #E5E7EB; padding:14px 16px;">
        <div style="font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Notes</div>
        <div style="font-size:14px; color:#374151; line-height:1.6;">{{ $review->notes ?? $review->description }}</div>
      </div>
    </td>
  </tr>
  @endif
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#EEF2FF; border-radius:8px; border-left:4px solid #6D28D9; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <span style="font-size:13px; color:#3730A3;">💡 <strong>Tip:</strong> Review your recent work, achievements, and any challenges you've faced. This helps you have a productive and meaningful conversation with your reviewer.</span>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">If you have questions about the review process, please contact your HR team.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
