<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Welcome to Lockmytimes</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:#F7F8FC; font-family:'Nunito Sans','Segoe UI',Arial,sans-serif; color:#1F2937; }
  .wrapper { max-width:600px; margin:40px auto; }
  .header  { background:linear-gradient(135deg,#6C7DF7 0%,#4A5BE8 100%); border-radius:16px 16px 0 0; padding:40px; text-align:center; }
  .header img { width:40px; height:40px; border-radius:10px; background:rgba(255,255,255,.2); padding:8px; margin-bottom:16px; }
  .header h1 { color:#fff; font-size:28px; font-weight:800; margin-bottom:6px; }
  .header p  { color:rgba(255,255,255,.85); font-size:15px; }
  .body    { background:#fff; padding:40px; border:1px solid #E5E7EB; border-top:none; }
  .body h2 { font-size:20px; font-weight:700; color:#1F2937; margin-bottom:8px; }
  .body p  { color:#6B7280; line-height:1.7; margin-bottom:16px; font-size:15px; }
  .cred-box { background:#F7F8FC; border:1px solid #E5E7EB; border-radius:12px; padding:24px; margin:24px 0; }
  .cred-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #E5E7EB; }
  .cred-row:last-child { border-bottom:none; }
  .cred-label { font-size:13px; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; }
  .cred-value { font-size:14px; font-weight:700; color:#1F2937; font-family:monospace; background:#EEF0FE; padding:4px 10px; border-radius:6px; }
  .btn { display:block; background:linear-gradient(135deg,#6C7DF7,#4A5BE8); color:#fff !important; text-decoration:none; text-align:center; padding:16px 32px; border-radius:12px; font-weight:700; font-size:16px; margin:28px 0; }
  .warning { background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:16px; font-size:13px; color:#92400e; margin-bottom:24px; }
  .trial-badge { display:inline-block; background:#EEF0FE; color:#3845C2; border-radius:20px; padding:6px 14px; font-size:13px; font-weight:700; margin-bottom:20px; }
  .footer { background:#F9FAFB; border:1px solid #E5E7EB; border-top:none; border-radius:0 0 16px 16px; padding:24px 40px; text-align:center; }
  .footer p { font-size:12px; color:#9CA3AF; line-height:1.6; }
  .footer a { color:#6C7DF7; text-decoration:none; }
  .steps { margin:20px 0; }
  .step  { display:flex; gap:14px; align-items:flex-start; margin-bottom:16px; }
  .step-num { width:28px; height:28px; background:#6C7DF7; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
  .step-text h4 { font-size:14px; font-weight:700; color:#1F2937; margin-bottom:2px; }
  .step-text p  { font-size:13px; color:#6B7280; margin:0; }
</style>
</head>
<body>
<div class="wrapper">

  {{-- Header --}}
  <div class="header">
    <div style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <h1>Your workspace is ready! 🎉</h1>
    <p>Welcome to Lockmytimes, {{ $adminName }}</p>
  </div>

  {{-- Body --}}
  <div class="body">

    <span class="trial-badge">🚀 {{ config('tenancy.trial_days', 14) }}-Day Free Trial — No charges until {{ $trialEnds }}</span>

    <h2>Hi {{ $adminName }},</h2>
    <p>
      Your <strong>{{ $tenant->company_name }}</strong> workspace has been provisioned and is ready to use.
      Your trial runs until <strong>{{ $trialEnds }}</strong> — no credit card charges until then.
    </p>

    {{-- Credentials --}}
    <div class="cred-box">
      <div class="cred-row">
        <span class="cred-label">Login URL</span>
        <span class="cred-value">{{ $adminUrl }}</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">Email</span>
        <span class="cred-value">{{ $tenant->contact_email }}</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value">{{ $password }}</span>
      </div>
    </div>

    <div class="warning">
      ⚠️ <strong>Please change your password</strong> after your first login. You will be prompted automatically.
    </div>

    <a href="{{ $adminUrl }}" class="btn">Open My Workspace →</a>

    {{-- Getting started steps --}}
    <h2 style="margin-bottom:16px;">Get started in 3 steps</h2>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-text">
          <h4>Set up your company profile</h4>
          <p>Add your logo, locations, departments, and working hours.</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-text">
          <h4>Add your employees</h4>
          <p>Import via CSV or add individually. Each gets their own login.</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-text">
          <h4>Generate your first QR code</h4>
          <p>Go to Attendance → Locations → Generate QR. Print and post it.</p>
        </div>
      </div>
    </div>

    <p style="margin-top:24px; font-size:14px;">
      Need help? Reply to this email or visit our
      <a href="https://lockmytimes.com" style="color:#6C7DF7;">help center</a>.
      We typically respond within a few hours.
    </p>

  </div>

  {{-- Footer --}}
  <div class="footer">
    <p>
      You're receiving this because you signed up at <a href="https://lockmytimes.com">lockmytimes.com</a>.<br>
      © {{ date('Y') }} Lockmytimes · All rights reserved.<br>
      <a href="#">Unsubscribe</a> · <a href="#">Privacy Policy</a>
    </p>
  </div>

</div>
</body>
</html>