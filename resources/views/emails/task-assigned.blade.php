@extends('emails.layout')
@php
  $headerTitle    = "New Task Assigned to You";
  $headerSubtitle = "You have a new task on project: " . ($task->project->name ?? 'N/A');
  $ctaUrl         = $portalUrl;
  $ctaText        = "View Task";
@endphp
@section('body')

<p>Hi <strong>{{ $employee->first_name }}</strong>,</p>
<p>A new task has been assigned to you. Here are the details:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Task Details</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <div style="font-size:17px; font-weight:700; color:#111827; margin-bottom:12px;">{{ $task->title }}</div>
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Project',  $task->project->name ?? '-'],
          ['Type',     ucfirst($task->type ?? '-')],
          ['Priority', ucfirst($task->priority ?? '-')],
          ['Due Date', isset($task->due_date) ? $task->due_date->format('D, M j, Y') : 'Not set'],
        ] as [$label, $value])
        <tr>
          <td style="padding:6px 0; width:110px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:6px 0; font-size:14px; color:#111827; font-weight:600;">
            @if($label === 'Priority')
              @php $colors = ['urgent'=>'#DC2626','high'=>'#F59E0B','normal'=>'#4F46E5','low'=>'#6B7280'] @endphp
              <span style="color:{{ $colors[$task->priority] ?? '#374151' }};">{{ $value }}</span>
            @else
              {{ $value }}
            @endif
          </td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  @if($task->description)
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:#fff; border-radius:8px; border:1px solid #E5E7EB; padding:14px 16px;">
        <div style="font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Description</div>
        <div style="font-size:14px; color:#374151; line-height:1.6;">{{ $task->description }}</div>
      </div>
    </td>
  </tr>
  @endif
</table>

<p style="color:#374151; font-size:14px;">Log in to the portal to view full task details, add comments, and update your progress.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} Team</strong></p>

@endsection
