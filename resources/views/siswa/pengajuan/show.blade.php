@extends('layouts.app')

@section('title', 'Detail Pengajuan')

@section('content')
@php
    $urutan = ['pending' => 'Menunggu', 'diproses' => 'Diproses', 'approved' => 'Siap Diambil', 'selesai' => 'Selesai'];
    $ditolak = $pengajuan->status === 'rejected';
    $posisi = array_search($pengajuan->status, array_keys($urutan), true);
@endphp

<x-page-header eyebrow="Detail pengajuan" :title="$pengajuan->labelJenisLayanan()">
    <x-status-badge :status="$pengajuan->status" class="!px-3.5 !py-1.5 !text-sm" />
    <a href="{{ route('siswa.pengajuan.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="h-4 w-4" /> Kembali</a>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        {{-- Kode tiket & progres --}}
        <div class="card card-pad">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-muted">Kode tiket</p>
                    <p class="mt-1 font-mono text-xl font-extrabold tracking-wide sm:text-2xl">{{ $pengajuan->kode_tiket }}</p>
                </div>
                <p class="text-sm text-muted">Diajukan {{ $pengajuan->created_at->translatedFormat('d F Y, H:i') }}</p>
            </div>

            @if ($ditolak)
                <div class="alert tone-rose mt-5" role="status">
                    <x-icon name="x-circle" size="mt-0.5 h-5 w-5" />
                    <div><p class="font-bold">Pengajuan ditolak</p>
                        <p class="mt-0.5">{{ $pengajuan->catatan_admin ?: 'Silakan hubungi petugas TU untuk informasi lebih lanjut.' }}</p></div>
                </div>
            @else
                <div class="stepper mt-6" role="list" aria-label="Progres pengajuan">
                    @foreach ($urutan as $kode => $label)
                        @php $i = $loop->index; $selesai = $posisi !== false && $i <= $posisi; @endphp
                        <div class="step" role="listitem" data-done="{{ $selesai ? 'true' : 'false' }}" data-current="{{ $i === $posisi ? 'true' : 'false' }}" @if ($i === $posisi) aria-current="step" @endif>
                            <span class="step-dot">@if ($selesai && $i < $posisi)<x-icon name="check" size="h-4 w-4" stroke="3.5" />@else{{ $i + 1 }}@endif</span>
                            <span class="step-label">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($pengajuan->catatan_admin)
                    <div class="alert tone-sky mt-5"><x-icon name="info" size="mt-0.5 h-5 w-5" /><p><strong>Catatan petugas:</strong> {{ $pengajuan->catatan_admin }}</p></div>
                @endif
            @endif
        </div>

        {{-- Rincian --}}
        <div class="card card-pad">
            <h2 class="card-title mb-4">Rincian pengajuan</h2>
            <dl class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Jenis layanan</dt><dd class="mt-1 font-semibold">{{ $pengajuan->labelJenisLayanan() }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Jenis dokumen</dt><dd class="mt-1 font-semibold">{{ $pengajuan->archive?->labelDetail() ?? ($pengajuan->documentType->nama ?? '—') }}</dd></div>
                @if($pengajuan->bentuk_dokumen)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Bentuk dokumen</dt><dd class="mt-1 font-semibold">{{ ucfirst($pengajuan->bentuk_dokumen) }}</dd></div>@endif
                @if ($pengajuan->jumlah_lembar)
                    <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Jumlah lembar</dt><dd class="mt-1 font-semibold">{{ $pengajuan->jumlah_lembar }} lembar</dd></div>
                @endif
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Tanggal dibutuhkan</dt><dd class="mt-1 font-semibold">{{ $pengajuan->tanggal_dibutuhkan?->translatedFormat('d F Y') ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-muted">Keperluan</dt><dd class="mt-1 leading-relaxed text-ink-2">{{ $pengajuan->keperluan }}</dd></div>
                @if ($pengajuan->tanggal_pengembalian)
                    <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Tanggal penyerahan</dt><dd class="mt-1 font-semibold">{{ $pengajuan->tanggal_pengembalian->translatedFormat('d F Y') }}</dd></div>
                @endif
            </dl>

            @if ($pengajuan->punyaBerkas())
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-3.5" style="background: rgb(var(--c-surface-2)); border: 1px solid rgb(var(--c-line))">
                    <span class="flex items-center gap-2.5 text-sm font-semibold"><span class="chip tone-sky h-9 w-9"><x-icon name="paperclip" size="h-4 w-4" /></span> Berkas pendukung terlampir</span>
                    <span class="flex gap-2">
                        <a href="{{ route('berkas.pengajuan', $pengajuan) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><x-icon name="eye" size="h-4 w-4" /> Lihat</a>
                        <a href="{{ route('berkas.pengajuan', [$pengajuan, 'unduh' => 1]) }}" class="btn btn-secondary btn-sm" data-no-progress><x-icon name="download" size="h-4 w-4" /> Unduh</a>
                    </span>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        {{-- QR Code pengambilan --}}
        @if ($qrUrl)
            <div class="card card-pad text-center tone-green">
                <span class="chip mx-auto mb-3 h-10 w-10"><x-icon name="qr" size="h-5 w-5" /></span>
                <h2 class="card-title">{{ $pengajuan->bentuk_dokumen === 'digital' ? 'QR Akses Dokumen Digital' : 'QR Verifikasi Pengambilan' }}</h2>
                <p class="mt-1 text-xs text-ink-2">{{ $pengajuan->bentuk_dokumen === 'digital' ? 'Pindai setelah login ke akun pemilik. QR dapat digunakan berulang kali selama akses aktif.' : 'Tunjukkan QR atau kode verifikasi kepada petugas TU.' }}</p>
                <div class="mx-auto mt-4 inline-block rounded-2xl bg-white p-3" style="box-shadow: var(--shadow-card)">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(176)->margin(0)->generate($qrUrl) !!}
                </div>
                <p class="mt-3 font-mono text-xs text-muted">{{ $pengajuan->kode_tiket }}</p>
                @if($pengajuan->bentuk_dokumen === 'fisik' && $kodeVerifikasi)<p class="mt-3 text-xs text-muted">Kode verifikasi manual</p><p class="font-mono text-lg font-bold tracking-wider">{{ $kodeVerifikasi }}</p><p class="mt-1 text-[11px] text-muted">Berlaku 14 hari dan hanya dapat digunakan untuk satu kali serah terima.</p>@else<p class="mt-1 text-[11px] text-muted">QR dapat digunakan berulang kali selama pengajuan disetujui dan akses tidak dicabut.</p>@endif
            </div>
        @endif

        @if($pengajuan->status === 'approved' && $pengajuan->bentuk_dokumen === 'digital' && (!$qrUrl || $pengajuan->digital_access_revoked_at))
            <div class="alert tone-amber"><x-icon name="lock" size="mt-0.5 h-5 w-5" /><p>Akses digital belum tersedia, telah dicabut, atau file versi pengajuan tidak tersedia. Hubungi petugas TU untuk pemeriksaan atau penerbitan ulang akses.</p></div>
        @endif
        @if($pengajuan->status === 'approved' && $pengajuan->bentuk_dokumen === 'fisik' && !$qrUrl)
            <div class="alert tone-amber"><x-icon name="clock" size="mt-0.5 h-5 w-5" /><p>Kode/QR verifikasi fisik belum tersedia atau telah kedaluwarsa. Hubungi petugas TU untuk menerbitkan kode baru.</p></div>
        @endif

        {{-- Riwayat status --}}
        <div class="card card-pad">
            <h2 class="card-title mb-4">Riwayat status</h2>
            @if ($pengajuan->riwayats->isEmpty())
                <p class="text-sm text-muted">Belum ada riwayat.</p>
            @else
                <div class="timeline">
                    @foreach ($pengajuan->riwayats as $r)
                        <div class="timeline-item tone-{{ $r->toneKe() }}">
                            <span class="timeline-dot"><span class="h-1.5 w-1.5 rounded-full" style="background: currentColor"></span></span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold">{{ $r->labelKe() }}</p>
                                @if ($r->catatan)<p class="mt-0.5 text-xs leading-snug text-ink-2">{{ $r->catatan }}</p>@endif
                                <p class="mt-0.5 text-xs text-muted">{{ $r->created_at->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
