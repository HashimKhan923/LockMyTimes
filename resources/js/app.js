import './bootstrap';
import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Chart from 'chart.js/auto';

Alpine.plugin(Collapse);

// Make libraries globally available for Blade pages
window.Alpine = Alpine;
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;
window.Chart = Chart;

gsap.registerPlugin(ScrollTrigger);

// ============================================================
// Global helpers exposed for Blade pages
// ============================================================

// Dark mode toggle
window.toggleDarkMode = function () {
    const html = document.documentElement;
    html.classList.toggle('dark');
    localStorage.setItem('lmt-theme', html.classList.contains('dark') ? 'dark' : 'light');
};

// Restore saved theme on load
(function () {
    const saved = localStorage.getItem('lmt-theme');
    if (saved === 'dark') document.documentElement.classList.add('dark');
})();

// Toast notification helper
window.lmtToast = function (message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    const colorClass = {
        info: 'bg-brand-500',
        success: 'bg-emerald-500',
        warning: 'bg-amber-500',
        error: 'bg-red-500',
    }[type] || 'bg-brand-500';

    toast.className = `fixed top-6 right-6 z-[100] ${colorClass} text-white px-5 py-3 rounded-xl shadow-pop font-medium text-sm`;
    toast.style.transform = 'translateX(120%)';
    toast.textContent = message;
    document.body.appendChild(toast);

    gsap.to(toast, { x: 0, duration: 0.4, ease: 'power2.out' });
    setTimeout(() => {
        gsap.to(toast, {
            x: '120%',
            duration: 0.3,
            ease: 'power2.in',
            onComplete: () => toast.remove(),
        });
    }, duration);
};

// Auto-animate elements with `data-lmt-anim`
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lmt-anim]').forEach((el) => {
        // Elements hidden at load time (e.g. inactive Alpine `x-show` tabs) have no layout, so their
        // scrollTrigger never fires — gsap.from() would set opacity:0 on them permanently, leaving
        // the tab blank forever even after Alpine later shows it. Skip animating anything not
        // currently visible; it just appears normally (no fade-in) once shown, which is correct.
        if (el.offsetParent === null) return;

        const animType = el.dataset.lmtAnim || 'fade-up';
        const delay = parseFloat(el.dataset.lmtDelay || 0);

        const animations = {
            'fade-up':   { y: 30, opacity: 0 },
            'fade-down': { y: -30, opacity: 0 },
            'fade-left': { x: 30, opacity: 0 },
            'fade-right':{ x: -30, opacity: 0 },
            'zoom':      { scale: 0.85, opacity: 0 },
        };

        const from = animations[animType] || animations['fade-up'];
        gsap.from(el, {
            ...from,
            duration: 0.8,
            delay,
            ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 85%', once: true },
        });
    });

    // Mark current sidebar link as active
    const path = window.location.pathname;
    document.querySelectorAll('.lmt-sidebar-link').forEach((link) => {
        if (link.getAttribute('href') === path) link.classList.add('active');
    });
});

Alpine.start();