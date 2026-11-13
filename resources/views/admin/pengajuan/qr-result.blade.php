@extends('layouts.app')

@section('title', 'Verifikasi QR')

@section('content')
@php
    $siap = $pengajuan->status === 'approved';
    $sudahSelesai = $pengajuan->status === 'selesai';
@endphp

<x-page-header
    eyebrow="Verifikasi QR"
    title="Konfirmasi pengambilan dokumen"
    subtitle="Periksa identitas siswa dan data berikut sebelum mengonfirmasi."
>
    <a href="{{ route('admin.scan-qr') }}" class="btn btn-secondary">
        <x-icon name="arrow-left" size="h-4 w-4" />
        Kembali ke scan
    </a>
</x-page-header>

<div class="mx-auto max-w-2xl">
    <div class="card card-pad">
        <div class="flex items-center gap-4">
            <x-avatar :user="$pengajuan->user" size="h-14 w-14 text-lg" />
            <div class="min-w-0">
                <p class="truncate text-lg font-extrabold">{{ $pengajuan->user->name }}</p>
                <p class="text-sm text-muted">
                    NIS {{ $pengajuan->user->nis_nip ?? '—' }} · {{ $pengajuan->user->kelas ?? '—' }}
                </p>
            </div>
            <x-status-badge :status="$pengajuan->status" class="ml-auto !px-3 !py-1.5 !text-sm" />
        </div>

        <dl class="mt-6 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Kode tiket</dt>
                <dd class="mt-1 font-mono font-bold">{{ $pengajuan->kode_tiket }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Layanan</dt>
                <dd class="mt-1 font-semibold">{{ $pengajuan->labelJenisLayanan() }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Jenis dokumen</dt>
                <dd class="mt-1 font-semibold">{{ $pengajuan->documentType->nama ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Diajukan</dt>
                <dd class="mt-1 font-semibold">{{ $pengajuan->created_at->translatedFormat('d F Y') }}</dd>
            </div>
        </dl>

        {{-- Kondisi dibuat terpisah (tanpa @elseif/@else) agar kompilasi Blade tetap sederhana. --}}
        @if ($siap)
            <form
                method="POST"
                action="{{ route('admin.pengajuan.qrKonfirmasi', $pengajuan) }}"
                class="mt-6 flex flex-col gap-2 sm:flex-row"
                data-confirm="Pastikan dokumen telah diserahkan kepada {{ $pengajuan->user->name }}. Pengajuan akan ditandai Selesai."
                data-confirm-title="Konfirmasi pengambilan"
                data-confirm-action="Ya, dokumen diserahkan"
            >
                @csrf
                <button type="submit" class="btn btn-success flex-1 !min-h-[3rem]">
                    <x-icon name="check-circle" size="h-5 w-5" />
                    Konfirmasi pengambilan
                </button>
                <a href="{{ route('admin.pengajuan.show', $pengajuan) }}" class="btn btn-secondary">
                    Lihat detail
                </a>
            </form>
        @endif

        @if ($sudahSelesai)
            <div class="alert tone-teal mt-6">
                <x-icon name="check-circle" size="mt-0.5 h-5 w-5" />
                <span>Dokumen ini sudah pernah dikonfirmasi diambil</span>
                @if ($pengajuan->tanggal_pengembalian)
                    <span>pada {{ $pengajuan->tanggal_pengembalian->translatedFormat('d F Y') }}.</span>
                @else
                    <span>.</span>
                @endif
            </div>
        @endif

        @if (! $siap && ! $sudahSelesai)
            <div class="alert tone-amber mt-6">
                <x-icon name="warning" size="mt-0.5 h-5 w-5" />
                <span>
                    Pengajuan belum berstatus <strong>Siap Diambil</strong>
                    (saat ini: {{ $pengajuan->statusLabel() }}), sehingga belum dapat dikonfirmasi.
                </span>
            </div>
        @endif
    </div>
</div>
@endsection
