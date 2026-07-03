@extends('emails.layout')
@php
  $headerTitle    = "Interview Scheduled 📅";
  $headerSubtitle = "You have an upcoming interview at " . $companyName;
  $ctaUrl         = $portalUrl ?? '#';
  $ctaText        = "View Interview Details";
@endphp
@section('body')

<p>Dear <strong>{{ $candidate->name }}</strong>,</p>
<p>We're pleased to inform you that your application has progressed and an interview has been scheduled. Here are the details:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Interview Details</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Position',      $interview->jobPosting->title ?? $interview->position ?? '-'],
          ['Date',          isset($interview->scheduled_at) ? $interview->scheduled_at->format('D, M j, Y') : '-'],
          ['Time',          isset($interview->scheduled_at) ? $interview->scheduled_at->format('g:i A') : '-'],
          ['Format',        $interview->format ?? 'In-person'],
          ['Location/Link', $interview->location ?? $interview->meeting_link ?? 'To be confirmed'],
          ['Interviewer',   $interview->interviewer?->name ?? '-'],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:140px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  @if(!empty($interview->notes))
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:#fff; border-radius:8px; border:1px solid #E5E7EB; padding:14px 16px;">
        <div style="font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Additional Notes</div>
        <div style="font-size:14px; color:#374151; line-height:1.6;">{{ $interview->notes }}</div>
      </div>
    </td>
  </tr>
  @endif
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF7ED; border-radius:8px; border-left:4px solid #F59E0B; margin-bottom:20px;">
  <tr>
    <td style="padding:14px 18px;">
      <span style="font-size:13px; color:#92400E;">💡 <strong>Tip:</strong> Please arrive or join 5 minutes early. Bring any required documents or portfolio items relevant to the role.</span>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">If you need to reschedule or have any questions, please reply to this email or contact HR directly.</p>
<p style="color:#374151; font-size:14px;">We look forward to meeting you!<br/>Regards,<br/><strong>{{ $companyName }} HR Team</strong></p>

@endsection
