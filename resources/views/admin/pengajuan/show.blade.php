@extends('layouts.app')

@section('title', 'Proses Pengajuan')

@section('content')
@if($pengajuan->jenis_layanan === 'legalisir' && $pengajuan->bentuk_dokumen === 'digital' && in_array($pengajuan->status, ['pending','diproses'], true))
<div class="card card-pad mb-4"><h2 class="card-title">Hasil Legalisir Digital</h2><p class="text-sm text-muted mt-1">Unggah hasil legalisir sebagai file terpisah; dokumen asli tetap dipertahankan.</p>@if($pengajuan->result_document_id)<p class="text-sm text-green-700 mt-2">Hasil tersedia: {{ $pengajuan->resultDocument?->original_filename }}</p>@endif<form method="POST" enctype="multipart/form-data" action="{{ route('admin.pengajuan.legalisir-result',$pengajuan) }}" class="mt-3 flex flex-wrap gap-3">@csrf<input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="input flex-1"><button class="btn btn-primary">Unggah Hasil</button></form></div>
@endif
@if($pengajuan->bentuk_dokumen === 'digital' && $pengajuan->status === 'approved')
<div class="card card-pad mb-4"><h2 class="card-title">Akses QR Digital</h2><p class="text-sm text-muted mt-1">QR dapat dipakai berulang kali. Mencabut atau menerbitkan ulang akses akan membatalkan QR sebelumnya.</p><div class="mt-3 flex flex-wrap gap-2">@if(!$pengajuan->digital_access_revoked_at)<form method="POST" action="{{ route('admin.pengajuan.digital-revoke',$pengajuan) }}">@csrf<button class="btn btn-secondary" onclick="return confirm('Cabut akses digital?')">Cabut Akses</button></form>@else<form method="POST" action="{{ route('admin.pengajuan.digital-reissue',$pengajuan) }}">@csrf<button class="btn btn-primary">Terbitkan Ulang QR</button></form>@endif</div></div>
@endif
@if($pengajuan->bentuk_dokumen === 'fisik' && $pengajuan->status === 'approved')
<div class="card card-pad mb-4"><h2 class="card-title">Kode Verifikasi Fisik</h2><p class="text-sm text-muted mt-1">Rotasi kode membatalkan kode lama dan menerbitkan kode baru untuk siswa.</p><form method="POST" action="{{ route('admin.pengajuan.physical-code-rotate',$pengajuan) }}" class="mt-3">@csrf<button class="btn btn-secondary">Rotasi Kode Verifikasi</button></form></div>
@endif
@php
    $tujuan = $pengajuan->statusTujuanValid();
    $opsi = collect($tujuan)->mapWithKeys(fn ($s) => [$s => \App\Models\Pengajuan::STATUS_LABEL[$s]])->all();
@endphp

<x-page-header eyebrow="Proses pengajuan" :title="$pengajuan->labelJenisLayanan()">
    <x-status-badge :status="$pengajuan->status" class="!px-3.5 !py-1.5 !text-sm" />
    <a href="{{ route('admin.pengajuan.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="h-4 w-4" /> Kembali</a>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card card-pad">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div><p class="text-xs font-bold uppercase tracking-wider text-muted">Kode tiket</p>
                    <p class="mt-1 font-mono text-xl font-extrabold tracking-wide">{{ $pengajuan->kode_tiket }}</p></div>
                <p class="text-sm text-muted">Diajukan {{ $pengajuan->created_at->translatedFormat('d F Y, H:i') }}</p>
            </div>
            <dl class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Jenis layanan</dt><dd class="mt-1 font-semibold">{{ $pengajuan->labelJenisLayanan() }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Jenis dokumen</dt><dd class="mt-1 font-semibold">{{ $pengajuan->archive?->labelDetail() ?? ($pengajuan->documentType->nama ?? '—') }}</dd></div>
                @if($pengajuan->bentuk_dokumen)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Bentuk dokumen</dt><dd class="mt-1 font-semibold">{{ ucfirst($pengajuan->bentuk_dokumen) }}</dd></div>@endif
                @if($pengajuan->academic_year)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Tahun ajaran</dt><dd class="mt-1 font-semibold">{{ $pengajuan->academic_year }}</dd></div>@endif
                @if($pengajuan->semester_akademik)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Semester</dt><dd class="mt-1 font-semibold">{{ $pengajuan->semester_akademik }}</dd></div>@endif
                @if($pengajuan->periode_rapor)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Periode rapor</dt><dd class="mt-1 font-semibold">{{ $pengajuan->periode_rapor }}</dd></div>@endif
                @if ($pengajuan->jumlah_lembar)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Jumlah lembar</dt><dd class="mt-1 font-semibold">{{ $pengajuan->jumlah_lembar }} lembar</dd></div>@endif
                <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Tanggal dibutuhkan</dt><dd class="mt-1 font-semibold">{{ $pengajuan->tanggal_dibutuhkan?->translatedFormat('d F Y') ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-muted">Keperluan</dt><dd class="mt-1 leading-relaxed text-ink-2">{{ $pengajuan->keperluan }}</dd></div>
                @if ($pengajuan->catatan_admin)<div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-muted">Catatan admin</dt><dd class="mt-1 text-ink-2">{{ $pengajuan->catatan_admin }}</dd></div>@endif
                @if ($pengajuan->verifier)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Terakhir diproses oleh</dt><dd class="mt-1 font-semibold">{{ $pengajuan->verifier->name }}</dd></div>@endif
                @if ($pengajuan->tanggal_pengembalian)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Penyerahan / pengembalian</dt><dd class="mt-1 font-semibold">{{ $pengajuan->tanggal_pengembalian->translatedFormat('d F Y') }} · {{ $pengajuan->kondisi_pengembalian }}</dd></div>@endif
            </dl>

            @if($pengajuan->resultDocument)
                <div class="mt-5 rounded-2xl p-3.5" style="background: rgb(var(--c-surface-2)); border: 1px solid rgb(var(--c-line))"><p class="text-sm font-semibold">Hasil legalisir: {{ $pengajuan->resultDocument->original_filename }}</p><a class="btn btn-secondary btn-sm mt-2" href="{{ route('admin.repository.download',$pengajuan->resultDocument) }}"><x-icon name="download" size="h-4 w-4" /> Unduh hasil legalisir</a></div>
            @endif
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

        {{-- Aksi --}}
        <div class="card card-pad">
            <h2 class="card-title mb-4">Tindakan</h2>
            @if ($pengajuan->status === 'approved')
                <form method="POST" action="{{ route('admin.pengajuan.pengembalian', $pengajuan) }}" class="mb-6 space-y-4 rounded-2xl p-4 tone-green"
                      style="background: rgb(var(--tone-soft))"
                      data-confirm="Catat penyerahan dokumen {{ $pengajuan->kode_tiket }} dan tandai pengajuan sebagai Selesai?" data-confirm-title="Catat penyerahan dokumen" data-confirm-action="Ya, catat selesai">
                    @csrf @method('PUT')
                    <p class="flex items-center gap-2 text-sm font-bold" style="color: rgb(var(--tone-ink))"><x-icon name="check-circle" size="h-5 w-5" /> Dokumen siap diserahkan / dikembalikan</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="tanggal_pengembalian" type="date" label="Tanggal penyerahan" required :value="now()->toDateString()" />
                        <x-input name="kondisi_pengembalian" label="Kondisi dokumen" required placeholder="mis. Baik, lengkap" />
                    </div>
                    <button type="submit" class="btn btn-success btn-sm"><x-icon name="check" size="h-4 w-4" /> Catat selesai</button>
                </form>
            @endif

            @if (empty($opsi))
                <div class="alert tone-slate"><x-icon name="info" size="mt-0.5 h-5 w-5" /> Pengajuan berstatus <strong>{{ $pengajuan->statusLabel() }}</strong> sudah final dan tidak dapat diubah lagi.</div>
            @else
                <form method="POST" action="{{ route('admin.pengajuan.status', $pengajuan) }}" class="space-y-4"
                      data-confirm="Ubah status pengajuan {{ $pengajuan->kode_tiket }}? Siswa akan menerima notifikasi." data-confirm-title="Ubah status pengajuan" data-confirm-action="Ya, ubah status">
                    @csrf @method('PUT')
                    <x-select name="status" label="Ubah status menjadi" required :options="$opsi" placeholder="— Pilih status —"
                              hint="Hanya status berikutnya yang valid yang ditampilkan." />
                    <x-textarea name="catatan_admin" label="Catatan untuk siswa" rows="3" hint="Wajib diisi bila status diubah menjadi Ditolak (alasan penolakan)." />
                    <button type="submit" class="btn btn-primary"><x-icon name="refresh" size="h-4 w-4" /> Simpan perubahan</button>
                </form>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card card-pad">
            <h2 class="card-title mb-4">Data pemohon</h2>
            <div class="flex items-center gap-3">
                <x-avatar :user="$pengajuan->user" size="h-12 w-12 text-base" />
                <div class="min-w-0"><p class="truncate font-bold">{{ $pengajuan->user->name }}</p><p class="truncate text-xs text-muted">{{ $pengajuan->user->email }}</p></div>
            </div>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-muted">NIS</dt><dd class="font-semibold">{{ $pengajuan->user->nis_nip ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Kelas</dt><dd class="font-semibold">{{ $pengajuan->user->kelas ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Telepon</dt><dd class="font-semibold">{{ $pengajuan->user->no_telp ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="card card-pad">
            <h2 class="card-title mb-4">Riwayat status</h2>
            <div class="timeline">
                @forelse ($pengajuan->riwayats as $r)
                    <div class="timeline-item tone-{{ $r->toneKe() }}">
                        <span class="timeline-dot"><span class="h-1.5 w-1.5 rounded-full" style="background: currentColor"></span></span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold">{{ $r->labelKe() }}</p>
                            @if ($r->catatan)<p class="mt-0.5 text-xs leading-snug text-ink-2">{{ $r->catatan }}</p>@endif
                            <p class="mt-0.5 text-xs text-muted">{{ $r->user->name ?? 'Sistem' }} · {{ $r->created_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted">Belum ada riwayat.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
