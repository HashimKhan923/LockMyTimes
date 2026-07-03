@extends('emails.layout')
@php
  $headerTitle    = "New Expense Submitted";
  $headerSubtitle = "{$expense->employee->full_name} submitted an expense for review";
  $ctaUrl         = $adminUrl;
  $ctaText        = "Review Expense";
@endphp
@section('body')

<p>Hi there,</p>
<p>A new expense has been submitted and is awaiting your approval:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#7C3AED,#6D28D9); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Expense Details</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Submitted By', $expense->employee->full_name],
          ['Category',     $expense->category?->name ?? '-'],
          ['Date',         $expense->expense_date->format('D, M j, Y')],
          ['Description',  $expense->description ?? '-'],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:130px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:10px; padding:16px 20px; text-align:center;">
        <div style="font-size:12px; color:rgba(255,255,255,0.75); text-transform:uppercase; letter-spacing:1px;">Amount</div>
        <div style="font-size:28px; font-weight:800; color:#fff; margin-top:4px;">${{ number_format($expense->amount, 2) }}</div>
      </div>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">Please review this expense in the admin portal and approve or reject it.</p>
<p style="color:#374151; font-size:14px;">— <strong>{{ $companyName }} HR System</strong></p>

@endsection
