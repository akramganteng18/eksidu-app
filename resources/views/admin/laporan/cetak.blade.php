@extends('layouts.app')

@section('title', 'Laporan Siap Cetak')

@section('content')
<div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-extrabold">Laporan siap cetak</h1>
        <p class="mt-1 text-sm text-ink-2">Tekan tombol cetak, lalu pilih <strong>Simpan sebagai PDF</strong> pada dialog printer untuk menyimpan sebagai berkas PDF.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.laporan.index', $filter) }}" class="btn btn-secondary"><x-icon name="arrow-left" size="h-4 w-4" /> Kembali</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><x-icon name="printer" size="h-4 w-4" /> Cetak / Simpan PDF</button>
    </div>
</div>

@if ($terpotong)
    <div class="alert tone-amber no-print mb-4"><x-icon name="warning" size="mt-0.5 h-5 w-5" /> Hanya 1.000 baris pertama yang ditampilkan. Persempit rentang tanggal atau gunakan Ekspor Excel untuk data lengkap.</div>
@endif

<div class="card print-sheet card-pad">
    <div class="mb-5 flex items-start justify-between gap-4 pb-4" style="border-bottom: 2px solid rgb(var(--c-line-strong))">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ config('eksidu.sekolah') }}</p>
            <h2 class="mt-1 text-xl font-extrabold">Laporan Pengajuan Layanan Dokumen</h2>
            <p class="mt-1 text-xs text-muted">
                @php
                    $ket = [];
                    if (! empty($filter['dari'])) $ket[] = 'dari ' . \Illuminate\Support\Carbon::parse($filter['dari'])->translatedFormat('d F Y');
                    if (! empty($filter['sampai'])) $ket[] = 'sampai ' . \Illuminate\Support\Carbon::parse($filter['sampai'])->translatedFormat('d F Y');
                    if (! empty($filter['status'])) $ket[] = 'status ' . (\App\Models\Pengajuan::STATUS_LABEL[$filter['status']] ?? $filter['status']);
                    if (! empty($filter['jenis_layanan'])) $ket[] = 'layanan ' . (\App\Models\Pengajuan::JENIS_SINGKAT[$filter['jenis_layanan']] ?? $filter['jenis_layanan']);
                    if (! empty($filter['kategori_layanan'])) $ket[] = 'kategori ' . (\App\Models\StudentDocumentArchive::CATEGORIES[$filter['kategori_layanan']] ?? $filter['kategori_layanan']);
                    if (! empty($filter['bentuk_dokumen'])) $ket[] = 'bentuk ' . $filter['bentuk_dokumen'];
                    if (! empty($filter['academic_year'])) $ket[] = 'tahun ajaran ' . $filter['academic_year'];
                    if (! empty($filter['semester_akademik'])) $ket[] = 'semester ' . $filter['semester_akademik'];
                    if (! empty($filter['periode_rapor'])) $ket[] = 'periode ' . $filter['periode_rapor'];
                @endphp
                {{ $ket ? 'Filter: ' . implode(', ', $ket) : 'Seluruh data' }} · {{ $pengajuans->count() }} pengajuan
            </p>
        </div>
        <x-logo :size="40" :subtitle="false" />
    </div>

    <div class="table-wrap">
        <table class="tbl">
            <thead><tr><th scope="col">No</th><th scope="col">Tiket</th><th scope="col">Siswa</th><th scope="col">Kelas</th><th scope="col">Layanan</th><th scope="col">Dokumen</th><th scope="col">Tahun ajaran</th><th scope="col">Semester</th><th scope="col">Periode</th><th scope="col">Bentuk</th><th scope="col">Status</th><th scope="col">Tanggal</th></tr></thead>
            <tbody>
                @forelse ($pengajuans as $i => $p)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        <td class="font-mono text-xs">{{ $p->kode_tiket }}</td>
                        <td class="font-semibold">{{ $p->user->name ?? '—' }}</td>
                        <td>{{ $p->user->kelas ?? '—' }}</td>
                        <td>{{ $p->labelJenisLayanan() }}</td>
                        <td>{{ $p->documentType->nama ?? '—' }}</td>
                        <td>{{ $p->academic_year ?? '—' }}</td>
                        <td>{{ $p->semester_akademik ? 'Semester '.$p->semester_akademik : '—' }}</td>
                        <td>{{ $p->periode_rapor ?? '—' }}</td>
                        <td>{{ $p->bentuk_dokumen ? ucfirst($p->bentuk_dokumen) : '—' }}</td>
                        <td>{{ $p->statusLabel() }}</td>
                        <td class="whitespace-nowrap">{{ $p->created_at->format('d-m-Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="py-8 text-center text-muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-5 text-xs text-muted">Dicetak oleh {{ $dicetakOleh->name }} pada {{ $tanggalCetak->translatedFormat('d F Y, H:i') }} WIB melalui {{ config('eksidu.nama') }}.</p>
</div>
@endsection
