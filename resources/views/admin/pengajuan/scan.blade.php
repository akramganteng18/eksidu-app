@extends('layouts.app')

@section('title', 'Scan QR Pengambilan')

@section('content')
<x-page-header eyebrow="Layanan dokumen" title="Scan QR pengambilan"
               subtitle="Arahkan kamera ke QR Code di perangkat siswa untuk memverifikasi dan mengonfirmasi pengambilan dokumen." />

<div class="grid gap-6 lg:grid-cols-5">
    <div class="card card-pad lg:col-span-3" x-data="pemindai()" x-init="mulai()">
        <div class="relative mx-auto aspect-square w-full max-w-md overflow-hidden rounded-2xl" style="background: rgb(var(--c-surface-3)); border: 1px solid rgb(var(--c-line))">
            <div id="reader" class="h-full w-full"></div>
            <div x-show="status === 'memuat'" class="skeleton absolute inset-0 !rounded-none" aria-hidden="true"></div>
            <div x-show="status === 'memuat'" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-sm font-semibold text-muted">
                <x-icon name="camera" size="h-8 w-8" /> Menyiapkan kamera…
            </div>
            <div x-show="status === 'gagal'" x-cloak class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
                <x-icon name="warning" size="h-8 w-8" />
                <p class="text-sm font-bold" x-text="pesan"></p>
                <button type="button" class="btn btn-secondary btn-sm" @click="mulai()">Coba lagi</button>
            </div>
        </div>
        <p class="mt-4 text-center text-xs text-muted" x-show="status === 'aktif'" x-cloak>Kamera aktif. Posisikan QR Code di dalam bingkai.</p>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <form method="POST" action="{{ route('admin.scan-qr.cari') }}" class="card card-pad space-y-4" novalidate>
            @csrf
            <div class="flex items-center gap-3"><span class="chip tone-sky h-10 w-10"><x-icon name="search" size="h-5 w-5" /></span>
                <div><h2 class="card-title">Cari manual</h2><p class="text-xs text-muted">Bila kamera tidak tersedia, masukkan kode tiket atau kode verifikasi fisik.</p></div></div>
            <x-input name="kode_tiket" label="Kode tiket / verifikasi" required autocomplete="off" class="font-mono" placeholder="EKSIDU-X7K9P2M8 atau nomor tiket" />
            <button type="submit" class="btn btn-primary btn-block">Buka pengajuan</button>
        </form>
        <div class="card card-pad tone-violet">
            <p class="mb-2 flex items-center gap-2 text-sm font-bold"><x-icon name="info" size="h-4 w-4" /> Cara kerja</p>
            <ol class="list-inside list-decimal space-y-1.5 text-sm text-ink-2">
                <li>Siswa membuka pengajuan berstatus <em>Siap Diambil</em>.</li>
                <li>Pindai QR Code yang tampil di layar siswa.</li>
                <li>Periksa data, lalu tekan <strong>Konfirmasi pengambilan</strong>.</li>
            </ol>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Pustaka pemindai dimuat dari CDN publik (versi dikunci). Tanpa internet, gunakan "Cari manual". --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" crossorigin="anonymous"></script>
<script>
    function pemindai() {
        return {
            status: 'memuat', pesan: '', scanner: null,
            async mulai() {
                this.status = 'memuat';
                if (typeof Html5Qrcode === 'undefined') {
                    this.status = 'gagal';
                    this.pesan = 'Pustaka pemindai tidak dapat dimuat (periksa koneksi internet). Gunakan pencarian manual.';
                    return;
                }
                try {
                    if (this.scanner) { try { await this.scanner.stop(); } catch (e) {} }
                    this.scanner = new Html5Qrcode('reader');
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 240, height: 240 } },
                        (teks) => this.tangkap(teks),
                        () => {}
                    );
                    this.status = 'aktif';
                } catch (e) {
                    this.status = 'gagal';
                    this.pesan = 'Kamera tidak dapat diakses. Izinkan akses kamera pada peramban, atau gunakan pencarian manual.';
                }
            },
            async tangkap(teks) {
                let url;
                try { url = new URL(teks); } catch (e) { url = null; }
                const allowedOrigins = new Set([window.location.origin]);
                try {
                    const configuredQrBase = @js(config('eksidu.public_url'));
                    if (configuredQrBase) allowedOrigins.add(new URL(configuredQrBase, window.location.href).origin);
                } catch (e) {}
                if (!url || !allowedOrigins.has(url.origin) || !url.pathname.includes('/pengajuan/qr/')) {
                    window.dispatchEvent(new CustomEvent('eksidu:toast', { detail: { tone: 'amber', title: 'QR tidak dikenali', message: 'QR Code ini bukan berasal dari alamat EKSiDU yang dikonfigurasi.' } }));
                    return;
                }
                try { await this.scanner.stop(); } catch (e) {}
                window.location.href = url.href;
            },
        };
    }
</script>
@endpush
