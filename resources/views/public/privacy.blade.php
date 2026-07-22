@extends('layouts.public')

@section('title', 'Privacy Policy — Lockmytimes')

@section('content')

{{-- ================================================================
     SIMPLE TOP BAR
================================================================ --}}
<nav class="border-b border-gray-100">
    <div class="lmt-container flex items-center justify-between h-16 px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="Lockmytimes" class="w-8 h-8 object-contain"/>
            <span class="text-xl font-bold text-brand-500" style="font-family:'Syne',sans-serif">Lockmytimes</span>
        </a>
        <a href="{{ route('home') }}" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors inline-flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to home
        </a>
    </div>
</nav>

{{-- ================================================================
     PRIVACY POLICY
================================================================ --}}
<section class="lmt-section">
    <div class="lmt-container px-6 max-w-3xl">
        <div class="mb-10">
            <div class="lmt-badge-brand mb-4">Legal</div>
            <h1 class="text-4xl md:text-5xl font-black text-ink">Privacy Policy</h1>
        </div>

        <div class="prose prose-slate max-w-none prose-headings:font-black prose-a:text-brand-500">

<p><strong>Effective Date:</strong> 25-March-2025</p>

<p>Welcome to <strong>LockMyTimes.com</strong>. Your privacy is important to us, and we are committed to protecting your personal data. This Privacy Policy explains how we collect, use, and safeguard your information when you visit our website and use our services.</p>

<br>

<h2>1. Information We Collect</h2>

<p>When you use <strong>LockMyTimes.com</strong>, we may collect the following types of information:</p>

<br />
<h3>1.1 Personal Information</h3>
<ul>
    <li>Name</li>
    <li>Email address</li>
    <li>Phone number (if provided)</li>
    <li>Payment details (for premium services)</li>
</ul>

<h3>1.2 Non-Personal Information</h3>
<ul>
    <li>Browser type and version</li>
    <li>Device information</li>
    <li>IP address</li>
    <li>Cookies and tracking technologies</li>
</ul>

<h3>1.3 Usage Data</h3>
<p>We collect <strong>how you interact</strong> with our platform, such as:</p>
<ul>
    <li>Time tracking logs</li>
    <li>Task management details</li>
    <li>Preferences and settings</li>
</ul>

<br>

<h2>2. How We Use Your Information</h2>

<p>We use your data to:</p>
<ul>
    <li>Provide and improve our services</li>
    <li>Personalize your experience</li>
    <li>Process transactions and payments</li>
    <li>Ensure platform security and prevent fraud</li>
    <li>Comply with legal obligations</li>
</ul>

        </div>
    </div>
</section>

@include('partials.public-footer')

@endsection
