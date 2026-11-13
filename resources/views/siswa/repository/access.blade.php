@extends('layouts.app')
@section('title', 'Akses Dokumen')
@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <x-page-header eyebrow="QR Repository EKSiDU" title="Akses dokumen disetujui" subtitle="QR valid. Dokumen ini ditampilkan setelah akun peminjam diverifikasi dan hanya dapat diakses selama izin masih aktif.">
        <x-badge tone="green">Akses aktif</x-badge>
    </x-page-header>
    <section class="card card-pad space-y-4">
        <div><p class="text-xs font-bold uppercase tracking-wide text-muted">Nama dokumen</p><h2 class="mt-1 text-xl font-extrabold">{{ $loan->document->title }}</h2></div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><p class="text-xs text-muted">Kategori</p><p class="font-semibold">{{ $loan->document->documentType?->nama ?? 'Dokumen akademik' }}</p></div>
            <div><p class="text-xs text-muted">Tahun ajaran</p><p class="font-semibold">{{ $loan->document->academic_year ?? '—' }}</p></div>
            <div><p class="text-xs text-muted">Masa berlaku sampai</p><p class="font-semibold">{{ $loan->expires_at?->format('d M Y H:i') ?? '—' }}</p></div>
            <div><p class="text-xs text-muted">Status</p><p class="font-semibold">Disetujui</p></div>
        </div>
        <div class="space-y-3">
            <div class="flex flex-wrap gap-2">
                <a class="btn btn-primary" href="{{ $previewUrl }}" target="_blank" rel="noopener"><x-icon name="eye" size="h-4 w-4"/> Buka dokumen</a>
                <a class="btn btn-secondary" href="{{ route('siswa.repository.download', $loan) }}"><x-icon name="download" size="h-4 w-4"/> Unduh dokumen</a>
            </div>
            @if ($loan->document->mime_type === 'application/pdf')
                <div class="overflow-hidden rounded-xl border" style="border-color: rgb(var(--c-line))">
                    <iframe src="{{ $previewUrl }}" title="Pratinjau {{ $loan->document->title }}" class="h-[65vh] min-h-[420px] w-full" loading="lazy"></iframe>
                </div>
            @elseif (in_array($loan->document->mime_type, ['image/jpeg', 'image/png'], true))
                <img src="{{ $previewUrl }}" alt="Pratinjau {{ $loan->document->title }}" class="mx-auto max-h-[65vh] rounded-xl object-contain" loading="lazy">
            @endif
        </div>
        <p class="text-xs text-muted">Dokumen tetap privat. QR tidak menggantikan login: hanya akun peminjam dan Admin/TU yang berwenang yang dapat membuka berkas. Jangan bagikan QR kepada orang lain.</p>
    </section>
</div>
@endsection
