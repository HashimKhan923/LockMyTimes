/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Brand colors (from your logo)
        brand: {
          50:  '#EEF0FE',
          100: '#DDE2FD',
          200: '#BCC5FB',
          300: '#9AA8FA',
          400: '#7989F8',
          500: '#6C7DF7',   // Primary
          600: '#4A5BE8',
          700: '#3845C2',
          800: '#28319B',
          900: '#1B2174',
        },
        accent: {
          50:  '#FFF8EC',
          100: '#FFEED0',
          200: '#FFDDA1',
          300: '#FFCB72',
          400: '#FFBA43',
          500: '#FFB547',
          600: '#E69A1F',
          700: '#B57717',
          800: '#83550F',
          900: '#523407',
        },
        ink: {
          DEFAULT: '#1F2937',
          soft: '#1F2937',
          muted: '#374151',
        },
        surface: {
          DEFAULT: '#FFFFFF',
          alt: '#F7F8FC',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
      borderRadius: {
        'xl': '14px',
        '2xl': '20px',
      },
      boxShadow: {
        'soft':  '0 4px 20px rgba(108, 125, 247, 0.08)',
        'pop':   '0 10px 40px rgba(108, 125, 247, 0.15)',
        'glow':  '0 0 30px rgba(108, 125, 247, 0.35)',
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.4s ease-out',
        'slide-down': 'slideDown 0.4s ease-out',
        'pulse-slow': 'pulse 3s infinite',
        'float': 'float 4s ease-in-out infinite',
        'shimmer': 'shimmer 2s linear infinite',
      },
      keyframes: {
        fadeIn:    { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
        slideUp:   { '0%': { transform: 'translateY(20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
        slideDown: { '0%': { transform: 'translateY(-20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
        float:     { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
        shimmer:   { '0%': { backgroundPosition: '-1000px 0' }, '100%': { backgroundPosition: '1000px 0' } },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
};