@extends('emails.layout')
@php
  $headerTitle    = "Your Payslip Is Ready 📄";
  $headerSubtitle = "Your salary for " . ($payslip->period_label ?? $payslip->payroll->period_label ?? 'this period') . " has been processed";
  $ctaUrl         = $portalUrl;
  $ctaText        = "View My Payslip";
@endphp
@section('body')

<p>Hi <strong>{{ $payslip->employee->first_name }}</strong>,</p>
<p>Your payslip is now available in the HR portal. Here's a summary:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FF; border-radius:12px; border:1px solid #E0E7FF; margin:20px 0; overflow:hidden;">
  <tr>
    <td style="background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:14px 24px;">
      <span style="color:#fff; font-weight:700; font-size:15px;">Payslip Summary</span>
    </td>
  </tr>
  <tr>
    <td style="padding:20px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        @foreach([
          ['Pay Period',    $payslip->period_label ?? $payslip->payroll->period_label ?? '-'],
          ['Basic Salary',  '$' . number_format($payslip->basic_salary ?? 0, 2)],
          ['Allowances',    '$' . number_format($payslip->total_allowances ?? 0, 2)],
          ['Deductions',    '−$' . number_format($payslip->total_deductions ?? 0, 2)],
        ] as [$label, $value])
        <tr>
          <td style="padding:7px 0; width:140px; font-size:13px; color:#9CA3AF; font-weight:500;">{{ $label }}</td>
          <td style="padding:7px 0; font-size:14px; color:#111827; font-weight:600;">{{ $value }}</td>
        </tr>
        @endforeach
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 24px 20px;">
      <div style="background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:10px; padding:16px 20px; text-align:center;">
        <div style="font-size:12px; color:rgba(255,255,255,0.75); text-transform:uppercase; letter-spacing:1px;">Net Pay</div>
        <div style="font-size:32px; font-weight:800; color:#fff; margin-top:4px;">${{ number_format($payslip->net_salary ?? 0, 2) }}</div>
      </div>
    </td>
  </tr>
</table>

<p style="color:#374151; font-size:14px;">Log into your employee portal to view and download your full payslip. If you notice any discrepancies, please contact HR immediately.</p>
<p style="color:#374151; font-size:14px;">Regards,<br/><strong>{{ $companyName }} Payroll Team</strong></p>

@endsection
