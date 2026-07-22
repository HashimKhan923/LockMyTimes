@extends('layouts.public')

@section('title', 'Terms & Conditions — Lockmytimes')

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
     TERMS & CONDITIONS
================================================================ --}}
<section class="lmt-section">
    <div class="lmt-container px-6 max-w-3xl">
        <div class="mb-10">
            <div class="lmt-badge-brand mb-4">Legal</div>
            <h1 class="text-4xl md:text-5xl font-black text-ink">Terms & Conditions</h1>
        </div>

        <div class="prose prose-slate max-w-none prose-headings:font-black prose-a:text-brand-500">

<p><strong>Effective Date:</strong> 25-March-2025</p>

<p>Welcome to <strong>LockMyTimes.com</strong>! By accessing or using our website and services, you agree to be bound by these <strong>Terms & Conditions</strong>. Please read them carefully. If you do not agree with any part of these terms, you must stop using our platform.</p>

<br/>

<h2>1. Definitions</h2>

<ul>
    <li><strong>"LockMyTimes.com," "we," "us," or "our"</strong> refers to the company operating the website.</li>
    <li><strong>"User," "you," or "your"</strong> refers to any individual or entity accessing our services.</li>
    <li><strong>"Services"</strong> refers to time tracking, scheduling, and other productivity tools provided on LockMyTimes.com.</li>
</ul>

<br/>

<h2>2. Use of Our Services</h2>

<p>By using our services, you agree that:</p>
<ul>
    <li> You are at least <strong>18 years old</strong> (or have parental consent if under 18).</li>
    <li> You will provide <strong>accurate and up-to-date</strong> information when registering.</li>
    <li> You will not use our services for <strong>illegal or unauthorized</strong> purposes.</li>
    <li> You will not attempt to <strong>disrupt, hack, or harm</strong> our platform.</li>
</ul>

<p>We reserve the right to <strong>suspend or terminate</strong> accounts violating these terms.</p>

<br/>

<h2>3. Account Registration & Security</h2>

<ul>
    <li>You are responsible for <strong>maintaining the confidentiality</strong> of your login credentials.</li>
    <li>Any activity under your account is <strong>your responsibility</strong>.</li>
    <li>If you suspect unauthorized access, contact us immediately at <strong>support@lockmytimes.com</strong>.</li>
</ul>

<br/>

<h2>4. Subscription & Payment (If Applicable)</h2>

<ul>
    <li>Some features of LockMyTimes.com may require a <strong>paid subscription</strong>.</li>
    <li>By purchasing a subscription, you agree to our <strong>pricing, billing, and renewal policies</strong>.</li>
    <li>Payments are processed securely via <strong>third-party payment gateways</strong>.</li>
    <li>Subscription fees are <strong>non-refundable</strong>, except where required by law.</li>
</ul>

<br/>

<h2>5. Intellectual Property</h2>

<ul>
    <li>All content on LockMyTimes.com (logo, design, software, text) is <strong>protected by copyright and intellectual property laws</strong>.</li>
    <li>You may <strong>not copy, distribute, or modify</strong> any part of our platform without prior written consent.</li>
</ul>

<br/>

<h2>6. User Content & Conduct</h2>

<ul>
    <li>You are responsible for any content you upload, submit, or share on our platform.</li>
    <li>Do not upload content that is <strong>illegal, offensive, or violates third-party rights</strong>.</li>
    <li>We reserve the right to <strong>remove content</strong> that violates these terms.</li>
</ul>

<br/>

<h2>7. Privacy Policy</h2>

<p>By using our services, you agree to our <a href="{{ route('privacy') }}">Privacy Policy</a>, which explains how we collect, use, and protect your data.</p>

<br/>

<h2>8. Service Availability & Limitations</h2>

<ul>
    <li>We strive to keep LockMyTimes.com <strong>available 24/7</strong>, but we do not guarantee uninterrupted service.</li>
    <li>We are <strong>not liable</strong> for any data loss, technical issues, or service disruptions.</li>
</ul>

<br/>

<h2>9. Limitation of Liability</h2>

<p>To the fullest extent permitted by law, <strong>LockMyTimes.com shall not be liable</strong> for:</p>
<ul>
    <li> Indirect, incidental, or consequential damages.</li>
    <li> Loss of data, profits, or business opportunities.</li>
    <li> Unauthorized access to your account due to negligence on your part.</li>
</ul>

<br/>

<h2>10. Changes to Terms</h2>

<p>We may <strong>update these Terms & Conditions</strong> from time to time. Continued use of our services means you accept any changes. Please check this page periodically.</p>

<br/>

<h2>11. Termination of Service</h2>

<p>We may <strong>suspend or terminate</strong> your account if you:</p>
<ul>
    <li>Violate these terms.</li>
    <li>Engage in fraudulent or harmful activities.</li>
    <li>Fail to comply with legal obligations.</li>
</ul>

<p>You may also terminate your account at any time by contacting <strong>support@lockmytimes.com</strong>.</p>

<br/>


<h2>12. Contact Us</h2>

<p>If you have any questions about these Terms & Conditions, reach out to us:</p>

<p><strong>Email:</strong> support@lockmytimes.com</p>

        </div>
    </div>
</section>

@include('partials.public-footer')

@endsection
