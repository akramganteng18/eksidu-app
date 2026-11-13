@extends('layouts.app')

@section('title', 'Pengajuan Masuk')

@section('content')
@php $adaFilter = request()->hasAny(['q', 'status', 'jenis_layanan', 'kategori_layanan', 'bentuk_dokumen', 'academic_year', 'semester_akademik', 'periode_rapor', 'dari', 'sampai']) && collect(request()->only(['q', 'status', 'jenis_layanan', 'kategori_layanan', 'bentuk_dokumen', 'academic_year', 'semester_akademik', 'periode_rapor', 'dari', 'sampai']))->filter()->isNotEmpty(); @endphp
<x-page-header eyebrow="Layanan dokumen" title="Pengajuan masuk" subtitle="Verifikasi dan proses pengajuan layanan dokumen dari siswa dan alumni.">
    <a href="{{ route('admin.scan-qr') }}" class="btn btn-secondary"><x-icon name="qr" size="h-4 w-4" /> Scan QR</a>
</x-page-header>

<form method="GET" action="{{ route('admin.pengajuan.index') }}" class="card card-pad mb-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <label for="q" class="label">Cari</label>
            <div class="input-icon"><x-icon name="search" size="h-4 w-4" class="icon-left" />
                <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Kode tiket, nama, atau NIS" autocomplete="off"></div>
        </div>
        <x-select name="status" label="Status" placeholder="Semua status" :options="\App\Models\Pengajuan::STATUS_LABEL" :selected="request('status')" />
        <x-select name="jenis_layanan" label="Layanan" placeholder="Semua layanan" :options="\App\Models\Pengajuan::JENIS_SINGKAT" :selected="request('jenis_layanan')" />
        <x-select name="kategori_layanan" label="Kategori" placeholder="Semua kategori" :options="\App\Models\StudentDocumentArchive::CATEGORIES" :selected="request('kategori_layanan')" />
        <x-select name="bentuk_dokumen" label="Bentuk" placeholder="Fisik/Digital" :options="['fisik' => 'Fisik', 'digital' => 'Digital']" :selected="request('bentuk_dokumen')" />
        <x-input name="academic_year" label="Tahun ajaran" :value="request('academic_year')" placeholder="2026/2027" />
        <x-select name="semester_akademik" label="Semester" placeholder="Semua" :options="['1' => 'Semester 1', '2' => 'Semester 2']" :selected="request('semester_akademik')" />
        <x-select name="periode_rapor" label="Periode" placeholder="Semua" :options="['tengah' => 'Tengah', 'akhir' => 'Akhir']" :selected="request('periode_rapor')" />
        <x-input name="dari" type="date" label="Dari tanggal" :value="request('dari')" />
        <x-input name="sampai" type="date" label="Sampai tanggal" :value="request('sampai')" />
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="search" size="h-4 w-4" /> Terapkan filter</button>
        @if ($adaFilter)<a href="{{ route('admin.pengajuan.index') }}" class="btn btn-secondary btn-sm">Reset</a>@endif
    </div>
</form>

<div class="card overflow-hidden">
    @if ($pengajuans->isEmpty())
        <x-empty-state :art="$adaFilter ? 'search' : 'inbox'" :title="$adaFilter ? 'Tidak ada pengajuan yang cocok' : 'Belum ada pengajuan masuk'"
                       :text="$adaFilter ? 'Ubah atau reset filter untuk melihat data lain.' : 'Pengajuan dari siswa akan tampil di sini.'">
            @if ($adaFilter)<a href="{{ route('admin.pengajuan.index') }}" class="btn btn-secondary btn-sm">Reset filter</a>@endif
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="tbl">
                <caption class="sr-only">Daftar pengajuan masuk</caption>
                <thead><tr><th scope="col">Tiket</th><th scope="col">Siswa</th><th scope="col">Layanan</th><th scope="col">Status</th><th scope="col">Diajukan</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach ($pengajuans as $p)
                        <tr>
                            <td><a href="{{ route('admin.pengajuan.show', $p) }}" class="link whitespace-nowrap font-mono text-xs">{{ $p->kode_tiket }}</a></td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <x-avatar :user="$p->user" size="h-8 w-8 text-xs" />
                                    <div class="min-w-0"><p class="truncate font-semibold">{{ $p->user->name }}</p><p class="text-xs text-muted">{{ $p->user->nis_nip ?? '—' }} · {{ $p->user->kelas ?? '—' }}</p></div>
                                </div>
                            </td>
                            <td class="text-ink-2">{{ $p->labelJenisLayanan() }}@if ($p->documentType)<span class="block text-xs text-muted">{{ $p->documentType->nama }}</span>@endif</td>
                            <td><x-status-badge :status="$p->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $p->created_at->format('d M Y') }}</td>
                            <td class="text-right"><a href="{{ route('admin.pengajuan.show', $p) }}" class="btn btn-secondary btn-sm">Proses <x-icon name="arrow-right" size="h-3.5 w-3.5" /></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad pt-2">{{ $pengajuans->links() }}</div>
    @endif
</div>
@endsection
