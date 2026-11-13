@extends('layouts.app')

@section('title', 'Riwayat Pengajuan')

@section('content')
@php $filter = request('status'); @endphp
<x-page-header eyebrow="Layanan saya" title="Riwayat pengajuan" subtitle="Seluruh pengajuan layanan dokumen yang pernah Anda buat.">
    <a href="{{ route('siswa.pengajuan.lacak.form') }}" class="btn btn-secondary"><x-icon name="search" size="h-4 w-4" /> Lacak tiket</a>
    <a href="{{ route('siswa.pengajuan.create') }}" class="btn btn-primary"><x-icon name="plus" size="h-4 w-4" /> Ajukan layanan</a>
</x-page-header>

<nav class="mb-4 flex flex-wrap gap-2" aria-label="Filter status">
    <a href="{{ route('siswa.pengajuan.index') }}" class="btn btn-sm {{ $filter ? 'btn-secondary' : 'btn-primary' }}" @if (! $filter) aria-current="true" @endif>Semua</a>
    @foreach (\App\Models\Pengajuan::STATUS_LABEL as $kode => $label)
        <a href="{{ route('siswa.pengajuan.index', ['status' => $kode]) }}" class="btn btn-sm {{ $filter === $kode ? 'btn-primary' : 'btn-secondary' }}" @if ($filter === $kode) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</nav>

<div class="card overflow-hidden">
    @if ($pengajuans->isEmpty())
        <x-empty-state :art="$filter ? 'search' : 'docs'" :title="$filter ? 'Tidak ada pengajuan dengan status ini' : 'Belum ada pengajuan'"
                       :text="$filter ? 'Coba pilih status lain atau tampilkan semua pengajuan.' : 'Pengajuan yang Anda buat akan tampil di sini beserta statusnya.'">
            @if ($filter)
                <a href="{{ route('siswa.pengajuan.index') }}" class="btn btn-secondary btn-sm">Tampilkan semua</a>
            @else
                <a href="{{ route('siswa.pengajuan.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="h-4 w-4" /> Ajukan layanan</a>
            @endif
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="tbl">
                <caption class="sr-only">Daftar pengajuan saya</caption>
                <thead><tr><th scope="col">Tiket</th><th scope="col">Layanan</th><th scope="col">Dokumen</th><th scope="col">Status</th><th scope="col">Diajukan</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach ($pengajuans as $p)
                        <tr>
                            <td><a href="{{ route('siswa.pengajuan.show', $p) }}" class="link whitespace-nowrap font-mono text-xs">{{ $p->kode_tiket }}</a></td>
                            <td class="font-semibold">{{ $p->labelJenisLayanan() }}</td>
                            <td class="text-ink-2">{{ $p->documentType->nama ?? '—' }}</td>
                            <td><x-status-badge :status="$p->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $p->created_at->format('d M Y') }}</td>
                            <td class="text-right"><a href="{{ route('siswa.pengajuan.show', $p) }}" class="btn btn-secondary btn-sm">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad pt-2">{{ $pengajuans->links() }}</div>
    @endif
</div>
@endsection
