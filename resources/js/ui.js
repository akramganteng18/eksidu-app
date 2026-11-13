// Interaksi UI bersama: toast, dialog konfirmasi, indikator memuat, animasi angka, input berkas.

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Toast ---------- */
export function toasts() {
    return {
        items: [],
        seq: 0,
        init() {
            let awal = [];
            try { awal = JSON.parse(this.$el.dataset.toasts || '[]'); } catch (e) { awal = []; }
            awal.forEach((t, i) => setTimeout(() => this.push(t), 150 * i));
            window.addEventListener('eksidu:toast', (e) => this.push(e.detail));
        },
        push({ tone = 'sky', title = '', message = '' }) {
            const id = ++this.seq;
            this.items.push({ id, tone, title, message });
            setTimeout(() => this.remove(id), tone === 'rose' ? 9000 : 5500);
        },
        remove(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    };
}

/* ---------- Dialog konfirmasi (form[data-confirm]) ---------- */
export function confirmDialog() {
    return {
        open: false,
        form: null,
        title: '',
        message: '',
        action: 'Ya, lanjutkan',
        danger: false,
        init() {
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (!form.dataset.confirm || form.dataset.confirmed === '1') return;
                e.preventDefault();
                e.stopImmediatePropagation();
                this.form = form;
                this.submitter = e.submitter || null;
                this.title = form.dataset.confirmTitle || 'Konfirmasi tindakan';
                this.message = form.dataset.confirm;
                this.action = form.dataset.confirmAction || 'Ya, lanjutkan';
                this.danger = form.dataset.confirmTone === 'danger';
                this.open = true;
                this.$nextTick(() => this.$refs.batal && this.$refs.batal.focus());
            }, true);
        },
        batal() { this.open = false; this.form = null; },
        lanjut() {
            const form = this.form;
            this.open = false;
            if (!form) return;
            form.dataset.confirmed = '1';
            if (form.requestSubmit) form.requestSubmit(this.submitter || undefined); else form.submit();
        },
    };
}

/* ---------- Indikator memuat & anti kirim ganda ---------- */
function progressBar() { return document.getElementById('top-progress'); }

function mulaiMemuat() {
    const bar = progressBar();
    if (!bar) return;
    bar.classList.add('on');
    clearTimeout(mulaiMemuat.timer);
    mulaiMemuat.timer = setTimeout(selesaiMemuat, 15000);
}

function selesaiMemuat() {
    const bar = progressBar();
    if (bar) bar.classList.remove('on');
    document.querySelectorAll('.is-loading').forEach((el) => { el.classList.remove('is-loading'); el.removeAttribute('aria-busy'); });
    document.querySelectorAll('form[data-submitting]').forEach((f) => { delete f.dataset.submitting; delete f.dataset.confirmed; });
}

export function initLoading() {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || !(form instanceof HTMLFormElement)) return;
        if (form.dataset.submitting === '1') { e.preventDefault(); return; }
        if (form.method && form.method.toLowerCase() === 'get' && !form.dataset.loading) return;

        form.dataset.submitting = '1';
        const tombol = e.submitter || form.querySelector('[type="submit"]');
        if (tombol && tombol.classList) { tombol.classList.add('is-loading'); tombol.setAttribute('aria-busy', 'true'); }
        mulaiMemuat();
    });

    document.addEventListener('click', (e) => {
        const a = e.target.closest && e.target.closest('a[href]');
        if (!a || e.defaultPrevented) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-progress')) return;
        const href = a.getAttribute('href') || '';
        if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return;
        if (a.origin !== window.location.origin) return;
        mulaiMemuat();
    });

    // Kembali lewat bfcache: bersihkan keadaan memuat.
    window.addEventListener('pageshow', selesaiMemuat);
}

/* ---------- Animasi angka pada kartu statistik ---------- */
export function initCountUp() {
    if (reduceMotion()) return;
    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = parseInt(el.dataset.count, 10);
        if (!Number.isFinite(target) || target <= 0) return;
        const durasi = 700;
        const mulai = performance.now();
        const langkah = (t) => {
            const p = Math.min(1, (t - mulai) / durasi);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(langkah);
        };
        el.textContent = '0';
        requestAnimationFrame(langkah);
    });
}

/* ---------- Input berkas dengan pemeriksaan ukuran di sisi klien (UX saja; server tetap memvalidasi) ---------- */
export function fileField(maxKb = 2048) {
    return {
        nama: '',
        ukuran: '',
        galat: '',
        pilih(event) {
            const f = event.target.files && event.target.files[0];
            this.galat = '';
            if (!f) { this.nama = ''; this.ukuran = ''; return; }
            if (f.size > maxKb * 1024) {
                event.target.value = '';
                this.nama = '';
                this.ukuran = '';
                this.galat = `Ukuran berkas melebihi batas ${maxKb >= 1024 ? (maxKb / 1024) + ' MB' : maxKb + ' KB'}.`;
                return;
            }
            this.nama = f.name;
            this.ukuran = f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB';
        },
    };
}
