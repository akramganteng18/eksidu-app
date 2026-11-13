{{-- Diletakkan PALING ATAS di <head>, sebelum CSS: menentukan tema sebelum halaman dicat sehingga tidak ada kilatan tema salah. --}}
{{-- Urutan prioritas: pilihan manual pengguna (localStorage) → preferensi sistem operasi → terang. --}}
<script>
    (function () {
        var tema = 'light';
        try {
            var simpan = localStorage.getItem('eksidu-theme');
            if (simpan === 'light' || simpan === 'dark') {
                tema = simpan;
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                tema = 'dark';
            }
        } catch (e) { /* localStorage tidak tersedia: tetap terang */ }
        document.documentElement.setAttribute('data-theme', tema);
        document.documentElement.style.colorScheme = tema;
    })();
</script>
