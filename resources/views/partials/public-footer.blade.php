{{-- ================================================================
     FOOTER
================================================================ --}}
<footer class="bg-ink text-white/60 py-16">
    <div class="lmt-container px-6">
        <div class="grid md:grid-cols-4 gap-10 mb-12">
            {{-- Brand --}}
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5 mb-4">
                    <img src="{{ asset('images/white_logo.png') }}" alt="Lockmytimes" class="w-8 h-8 object-contain"/>
                    <span class="text-white text-xl font-bold" style="font-family:'Syne',sans-serif">Lockmytimes</span>
                </div>
                <p class="text-sm leading-relaxed mb-4">
                    All-in-one HR platform built for modern US businesses. QR attendance, payroll, performance, and more.
                </p>
                <a href="mailto:sales@lockmytimes.com" class="text-sm font-medium text-white/80 hover:text-white transition-colors inline-flex items-center gap-2 mb-4">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                    sales@lockmytimes.com
                </a>
                <div class="flex gap-3">
                    <a href="https://www.facebook.com/share/17hobPgyAf/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-brand-500 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/lockmytimes" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-brand-500 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/@lockmytimes" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-brand-500 flex items-center justify-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                            <path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"></path>
                            <path d="m10 15 5-3-5-3z"></path>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Links --}}
            @php
            $footerLinks = [
                'Product' => [
                    'Features'      => url('/').'#features',
                    'How It Works'  => url('/').'#how',
                    'Pricing'       => url('/').'#pricing',
                    'FAQ'           => url('/').'#faq',
                    'Contact Us'    => url('/').'#contact',
                ],
                'Legal' => [
                    'Terms & Conditions' => route('terms'),
                    'Privacy Policy'     => route('privacy'),
                ],
            ];
            @endphp
            @foreach($footerLinks as $group => $links)
            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-wider mb-4">{{ $group }}</h4>
                <ul class="space-y-2.5">
                    @foreach($links as $label => $href)
                    <li><a href="{{ $href }}" class="text-sm hover:text-white transition-colors">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>

        <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
            <p>© {{ date('Y') }} Lockmytimes. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    SOC 2 Compliant
                </span>
                <span class="flex items-center gap-1.5">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-brand-400"></i>
                    256-bit Encryption
                </span>
                <span class="flex items-center gap-1.5">
                    <i data-lucide="server" class="w-3.5 h-3.5 text-amber-400"></i>
                    99.9% Uptime SLA
                </span>
            </div>
        </div>
    </div>
</footer>
