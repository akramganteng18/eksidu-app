/** @type {import('tailwindcss').Config} */
const rgb = (v) => `rgb(var(${v}) / <alpha-value>)`;

export default {
  // Tema dikendalikan atribut data-theme pada <html> (lihat resources/views/partials/theme-init.blade.php).
  darkMode: ['selector', '[data-theme="dark"]'],
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        canvas: rgb('--c-canvas'),
        surface: rgb('--c-surface'),
        'surface-2': rgb('--c-surface-2'),
        'surface-3': rgb('--c-surface-3'),
        line: rgb('--c-line'),
        'line-strong': rgb('--c-line-strong'),
        ink: rgb('--c-ink'),
        'ink-2': rgb('--c-ink-2'),
        muted: rgb('--c-muted'),
        brand: rgb('--c-brand'),
        'brand-hover': rgb('--c-brand-hover'),
        'brand-fg': rgb('--c-brand-fg'),
        'brand-soft': rgb('--c-brand-soft'),
        'brand-ink': rgb('--c-brand-ink'),
      },
      fontFamily: {
        sans: ['"Segoe UI Variable Text"', '"Segoe UI"', 'system-ui', '-apple-system', 'Roboto', '"Helvetica Neue"', 'Arial', 'sans-serif'],
        display: ['"Segoe UI Variable Display"', '"Segoe UI"', 'system-ui', '-apple-system', 'Roboto', 'Arial', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', '"Liberation Mono"', 'monospace'],
      },
      borderRadius: {
        card: 'var(--r-card)',
      },
      boxShadow: {
        card: 'var(--shadow-card)',
        pop: 'var(--shadow-pop)',
      },
      keyframes: {
        'fade-up': { '0%': { opacity: '0', transform: 'translateY(8px)' }, '100%': { opacity: '1', transform: 'none' } },
        'toast-in': { '0%': { opacity: '0', transform: 'translateX(16px)' }, '100%': { opacity: '1', transform: 'none' } },
        'grow-y': { '0%': { transform: 'scaleY(0)' }, '100%': { transform: 'scaleY(1)' } },
        shimmer: { '100%': { transform: 'translateX(100%)' } },
        spin: { to: { transform: 'rotate(360deg)' } },
      },
      animation: {
        'fade-up': 'fade-up .35s ease-out both',
        'toast-in': 'toast-in .3s ease-out both',
      },
    },
  },
  plugins: [],
}
