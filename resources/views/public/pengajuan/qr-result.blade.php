@extends('layouts.guest')

@section('title', 'Ringkasan QR Pengajuan')

@section('content')
<div class="w-full max-w-lg space-y-5">
    <div class="text-center">
        <x-logo :size="48" />
        <p class="mt-5 text-xs font-extrabold uppercase tracking-[.16em] text-brand">QR Pengajuan EKSiDU</p>
        <h1 class="mt-2 text-2xl font-extrabold">Ringkasan permohonan dokumen</h1>
        <p class="mt-2 text-sm text-ink-2">Informasi ini membantu petugas atau pemindai memastikan layanan dan dokumen yang dimohon.</p>
    </div>

    <section class="card card-pad space-y-4">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-bold uppercase tracking-wide text-muted">Nomor tiket</span>
            <span class="font-mono text-sm font-extrabold">{{ $pengajuan->kode_tiket }}</span>
        </div>
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-bold uppercase tracking-wide text-muted">Status</span>
            <x-status-badge :status="$pengajuan->status" />
        </div>
        <div class="border-t pt-4" style="border-color: rgb(var(--c-line))">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Layanan</dt>
                    <dd class="mt-1 font-semibold">{{ $pengajuan->labelJenisLayanan() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Dokumen yang dimohon</dt>
                    <dd class="mt-1 font-semibold">{{ $pengajuan->documentType?->nama ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Tanggal pengajuan</dt>
                    <dd class="mt-1 font-semibold">{{ $pengajuan->created_at->translatedFormat('d F Y') }}</dd>
                </div>
                @if ($pengajuan->tanggal_dibutuhkan)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-muted">Tanggal dibutuhkan</dt>
                        <dd class="mt-1 font-semibold">{{ $pengajuan->tanggal_dibutuhkan->translatedFormat('d F Y') }}</dd>
                    </div>
                @endif
                @if ($pengajuan->jumlah_lembar)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-muted">Jumlah legalisir</dt>
                        <dd class="mt-1 font-semibold">{{ $pengajuan->jumlah_lembar }} lembar</dd>
                    </div>
                @endif
            </dl>
        </div>
    </section>

    <div class="alert tone-sky">
        <x-icon name="info" size="mt-0.5 h-5 w-5" />
        <p class="text-sm">QR ini menampilkan ringkasan permohonan, bukan salinan digital dokumen fisik. Rapor, ijazah, atau dokumen asli tetap diserahkan langsung oleh petugas TU setelah verifikasi.</p>
    </div>
    <p class="text-center text-xs text-muted">{{ config('eksidu.sekolah') }} · {{ config('eksidu.tagline') }}</p>
</div>
@endsection
