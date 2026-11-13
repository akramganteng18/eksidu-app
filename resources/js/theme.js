// Sistem tema EKSiDU: Light (Creative Academic) / Dark (Dark Premium).
// Tema awal sudah diterapkan SEBELUM render oleh partials/theme-init.blade.php (mencegah kilatan).
// File ini hanya menangani pergantian oleh pengguna.

const KEY = 'eksidu-theme';

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export const storedTheme = () => {
    try { return localStorage.getItem(KEY); } catch (e) { return null; }
};

export const saveTheme = (theme) => {
    try { localStorage.setItem(KEY, theme); } catch (e) { /* mode privat: abaikan */ }
};

export const currentTheme = () =>
    document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';

export function applyTheme(theme, animate = true) {
    const root = document.documentElement;

    if (animate && !reduceMotion()) {
        root.classList.add('theme-switching');
        clearTimeout(applyTheme.timer);
        applyTheme.timer = setTimeout(() => root.classList.remove('theme-switching'), 450);
    }

    root.setAttribute('data-theme', theme);
    root.style.colorScheme = theme;

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'dark' ? '#0e1013' : '#fbf8f1');
}

export default function themeToggle() {
    return {
        theme: currentTheme(),

        init() {
            // Sinkron antar tombol / antar tab.
            window.addEventListener('eksidu:theme', (e) => { this.theme = e.detail; });
            window.addEventListener('storage', (e) => {
                if (e.key === KEY && (e.newValue === 'dark' || e.newValue === 'light')) {
                    this.theme = e.newValue;
                    applyTheme(e.newValue, false);
                }
            });

            // Ikuti pengaturan OS HANYA bila pengguna belum memilih secara manual.
            const mq = window.matchMedia('(prefers-color-scheme: dark)');
            const onChange = (e) => {
                if (storedTheme()) return;
                this.theme = e.matches ? 'dark' : 'light';
                applyTheme(this.theme);
            };
            if (mq.addEventListener) mq.addEventListener('change', onChange);
        },

        toggle() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            saveTheme(this.theme);
            applyTheme(this.theme);
            window.dispatchEvent(new CustomEvent('eksidu:theme', { detail: this.theme }));
        },
    };
}
