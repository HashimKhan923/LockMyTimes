<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<title>{{ $subject ?? 'HR Notification' }}</title>
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
  * { box-sizing: border-box; }
  body { margin: 0; padding: 0; background-color: #F0F2FF; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  a { color: #4F46E5; text-decoration: none; }
  p { margin: 0 0 16px 0; }
  .preheader { display: none; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: transparent; }
</style>
</head>
<body>

{{-- Preheader (shows in inbox preview) --}}
<div class="preheader">{{ $preheader ?? ($subject ?? '') }}</div>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F0F2FF; padding: 32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; border-radius:16px; overflow:hidden; box-shadow: 0 4px 24px rgba(79,70,229,0.10);">

  {{-- ══════════ HEADER ══════════ --}}
  <tr>
    <td style="background: linear-gradient(135deg, #4F46E5 0%, #6D28D9 50%, #7C3AED 100%); padding: 36px 40px; text-align: center;">
      {{-- Logo --}}
      @if(!empty($companyLogo))
        <img src="{{ $companyLogo }}" alt="{{ $companyName }}" height="52"
             style="max-height:52px; max-width:180px; object-fit:contain; border-radius:8px; margin-bottom:14px; display:block; margin-left:auto; margin-right:auto;"/>
      @else
        <div style="width:60px; height:60px; border-radius:16px; background:rgba(255,255,255,0.2); display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; font-size:22px; font-weight:800; color:#fff; letter-spacing:-1px; line-height:60px; text-align:center;">
          {{ strtoupper(substr($companyName, 0, 2)) }}
        </div>
      @endif
      <div style="color:#fff; font-size:20px; font-weight:700; letter-spacing:-0.3px;">{{ $companyName }}</div>
      <div style="color:rgba(255,255,255,0.65); font-size:12px; margin-top:3px; letter-spacing:0.5px; text-transform:uppercase;">HR Platform</div>
    </td>
  </tr>

  {{-- ══════════ TITLE BANNER ══════════ --}}
  @if(!empty($headerTitle))
  <tr>
    <td style="background:#F8F9FF; padding: 24px 40px 20px; border-left: 4px solid #4F46E5;">
      <div style="font-size:20px; font-weight:700; color:#1E1B4B; letter-spacing:-0.3px; line-height:1.3;">{{ $headerTitle }}</div>
      @if(!empty($headerSubtitle))
        <div style="font-size:14px; color:#6B7280; margin-top:4px;">{{ $headerSubtitle }}</div>
      @endif
    </td>
  </tr>
  @endif

  {{-- ══════════ BODY ══════════ --}}
  <tr>
    <td style="background:#FFFFFF; padding: 32px 40px; font-size:15px; color:#374151; line-height:1.7;">
      @yield('body')
    </td>
  </tr>

  {{-- ══════════ CTA BUTTON ══════════ --}}
  @if(!empty($ctaUrl) && !empty($ctaText))
  <tr>
    <td style="background:#FFFFFF; padding: 0 40px 36px; text-align: center;">
      <a href="{{ $ctaUrl }}"
         style="display:inline-block; background: linear-gradient(135deg, #4F46E5, #7C3AED); color:#fff; font-size:15px; font-weight:600; padding: 14px 36px; border-radius:10px; text-decoration:none; letter-spacing:0.1px; box-shadow: 0 4px 14px rgba(79,70,229,0.35);">
        {{ $ctaText }}
      </a>
    </td>
  </tr>
  @endif

  {{-- ══════════ DIVIDER ══════════ --}}
  <tr>
    <td style="background:#FFFFFF; padding: 0 40px;">
      <div style="height:1px; background: linear-gradient(90deg, transparent, #E5E7EB, transparent);"></div>
    </td>
  </tr>

  {{-- ══════════ FOOTER ══════════ --}}
  <tr>
    <td style="background:#FAFAFA; padding: 24px 40px; text-align:center; border-radius: 0 0 16px 16px;">
      <div style="font-size:13px; color:#9CA3AF; line-height:1.6;">
        This email was sent by <strong style="color:#6B7280;">{{ $companyName }}</strong><br/>
        <span style="font-size:11px; color:#D1D5DB;">Powered by <a href="https://lockmytimes.com" style="color:#9CA3AF; text-decoration:none;">Lockmytimes</a> &nbsp;·&nbsp; © {{ date('Y') }} All rights reserved</span>
      </div>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
