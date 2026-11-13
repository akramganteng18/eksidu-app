@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
@php
    $query = request()->only(['dari', 'sampai', 'status', 'jenis_layanan', 'kategori_layanan', 'bentuk_dokumen', 'academic_year', 'semester_akademik', 'periode_rapor']);
    $adaFilter = collect($query)->filter()->isNotEmpty();
    $totalFilter = (int) $perStatus->sum();
@endphp
<x-page-header eyebrow="Laporan & sistem" title="Laporan pengajuan" subtitle="Rekap pengajuan berdasarkan siswa, kategori, bentuk dokumen, tahun ajaran, semester, periode, status, dan rentang tanggal.">
    <a href="{{ route('admin.laporan.cetak', $query) }}" target="_blank" rel="noopener" class="btn btn-secondary"><x-icon name="printer" size="h-4 w-4" /> Cetak / PDF</a>
    <a href="{{ route('admin.laporan.excel', $query) }}" class="btn btn-primary" data-no-progress><x-icon name="table" size="h-4 w-4" /> Ekspor Excel</a>
</x-page-header>

<form method="GET" action="{{ route('admin.laporan.index') }}" class="card card-pad mb-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-input name="dari" type="date" label="Dari tanggal" :value="request('dari')" />
        <x-input name="sampai" type="date" label="Sampai tanggal" :value="request('sampai')" />
        <x-select name="status" label="Status" placeholder="Semua status" :options="\App\Models\Pengajuan::STATUS_LABEL" :selected="request('status')" />
        <x-select name="jenis_layanan" label="Layanan" placeholder="Semua layanan" :options="\App\Models\Pengajuan::JENIS_SINGKAT" :selected="request('jenis_layanan')" />
        <x-select name="kategori_layanan" label="Kategori dokumen" placeholder="Semua kategori" :options="\App\Models\StudentDocumentArchive::CATEGORIES" :selected="request('kategori_layanan')" />
        <x-select name="bentuk_dokumen" label="Bentuk dokumen" placeholder="Semua bentuk" :options="['fisik' => 'Fisik', 'digital' => 'Digital']" :selected="request('bentuk_dokumen')" />
        <x-input name="academic_year" label="Tahun ajaran" :value="request('academic_year')" placeholder="2026/2027" />
        <x-select name="semester_akademik" label="Semester" placeholder="Semua semester" :options="['1' => 'Semester 1', '2' => 'Semester 2']" :selected="request('semester_akademik')" />
        <x-select name="periode_rapor" label="Periode rapor" placeholder="Semua periode" :options="['tengah' => 'Tengah Semester', 'akhir' => 'Akhir Semester']" :selected="request('periode_rapor')" />
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="search" size="h-4 w-4" /> Terapkan</button>
        @if ($adaFilter)<a href="{{ route('admin.laporan.index') }}" class="btn btn-secondary btn-sm">Reset</a>@endif
    </div>
</form>

{{-- Ringkasan sesuai filter --}}
<section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-6" aria-label="Ringkasan hasil filter">
    <x-stat-card label="Total" :value="$totalFilter" tone="teal" icon="document" lime class="col-span-2 lg:col-span-1" />
    @foreach (['pending' => ['amber', 'clock'], 'diproses' => ['sky', 'refresh'], 'approved' => ['green', 'check-circle'], 'selesai' => ['violet', 'stamp'], 'rejected' => ['rose', 'x-circle']] as $kode => [$tone, $ikon])
        <x-stat-card :label="\App\Models\Pengajuan::STATUS_LABEL[$kode]" :value="(int) ($perStatus[$kode] ?? 0)" :tone="$tone" :icon="$ikon" />
    @endforeach
</section>

<div class="card overflow-hidden">
    @if ($pengajuans->isEmpty())
        <x-empty-state art="search" title="Tidak ada data pada filter ini" text="Ubah rentang tanggal atau filter lain untuk melihat laporan." />
    @else
        <div class="table-wrap">
            <table class="tbl">
                <caption class="sr-only">Laporan pengajuan</caption>
                <thead><tr><th scope="col">Tiket</th><th scope="col">Siswa</th><th scope="col">Kelas</th><th scope="col">Layanan</th><th scope="col">Dokumen</th><th scope="col">Tahun ajaran</th><th scope="col">Semester</th><th scope="col">Periode</th><th scope="col">Bentuk</th><th scope="col">Status</th><th scope="col">Tanggal</th></tr></thead>
                <tbody>
                    @foreach ($pengajuans as $p)
                        <tr>
                            <td><a href="{{ route('admin.pengajuan.show', $p) }}" class="link whitespace-nowrap font-mono text-xs">{{ $p->kode_tiket }}</a></td>
                            <td class="font-semibold">{{ $p->user->name ?? '—' }}</td>
                            <td class="text-ink-2">{{ $p->user->kelas ?? '—' }}</td>
                            <td class="text-ink-2">{{ $p->labelJenisLayanan() }}</td>
                            <td class="text-ink-2">{{ $p->documentType->nama ?? '—' }}</td>
                            <td>{{ $p->academic_year ?? '—' }}</td><td>{{ $p->semester_akademik ? 'Semester '.$p->semester_akademik : '—' }}</td><td>{{ $p->periode_rapor ?? '—' }}</td><td>{{ $p->bentuk_dokumen ? ucfirst($p->bentuk_dokumen) : '—' }}</td>
                            <td><x-status-badge :status="$p->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $p->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad pt-2">{{ $pengajuans->links() }}</div>
    @endif
</div>
@endsection
