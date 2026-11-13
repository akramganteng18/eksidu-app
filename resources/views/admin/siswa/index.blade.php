@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
<x-page-header eyebrow="Data master" title="Data siswa" subtitle="Kelola akun siswa dan alumni. Akun dibuat oleh admin agar identitas terverifikasi.">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.siswa.import.create') }}" class="btn btn-secondary"><x-icon name="upload" size="h-4 w-4" /> Impor CSV</a>
        <a href="{{ route('admin.siswa.create') }}" class="btn btn-primary"><x-icon name="plus" size="h-4 w-4" /> Tambah siswa</a>
    </div>
</x-page-header>

<form method="GET" action="{{ route('admin.siswa.index') }}" class="card card-pad mb-6">
    <label for="q" class="label">Cari siswa</label>
    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="input-icon flex-1"><x-icon name="search" size="h-4 w-4" class="icon-left" />
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Nama, NIS, email, atau kelas" autocomplete="off"></div>
        <button type="submit" class="btn btn-primary">Cari</button>
        @if (request('q'))<a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary">Reset</a>@endif
    </div>
</form>

<div class="card overflow-hidden">
    @if ($siswa->isEmpty())
        <x-empty-state :art="request('q') ? 'search' : 'docs'" :title="request('q') ? 'Siswa tidak ditemukan' : 'Belum ada data siswa'"
                       :text="request('q') ? 'Periksa ejaan atau coba kata kunci lain.' : 'Tambahkan akun siswa pertama agar mereka dapat mengajukan layanan.'">
            <a href="{{ route('admin.siswa.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="h-4 w-4" /> Tambah siswa</a>
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="tbl tbl-cards-mobile">
                <caption class="sr-only">Daftar siswa</caption>
                <thead><tr><th scope="col">Siswa</th><th scope="col">NIS</th><th scope="col">Kelas</th><th scope="col">Status akun</th><th scope="col" class="text-right">Pengajuan</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach ($siswa as $s)
                        <tr>
                            <td><div class="flex items-center gap-2.5"><x-avatar :user="$s" size="h-9 w-9 text-xs" />
                                <div class="min-w-0"><p class="truncate font-semibold">{{ $s->name }}</p><p class="truncate text-xs text-muted">{{ $s->email }}</p></div></div></td>
                            <td class="num text-ink-2">{{ $s->nis_nip ?? '—' }}</td>
                            <td class="text-ink-2">{{ $s->kelas ?? '—' }}</td>
                            <td><x-badge :tone="$s->aktif ? 'green' : 'slate'">{{ $s->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                            <td class="num text-right font-semibold">{{ $s->pengajuans_count }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('admin.siswa.edit', $s) }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" size="h-3.5 w-3.5" /> Ubah</a>
                                    <form method="POST" action="{{ route('admin.siswa.destroy', $s) }}"
                                          data-confirm="Hapus data siswa {{ $s->name }}? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus siswa" data-confirm-tone="danger" data-confirm-action="Ya, hapus">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-sm btn-icon !h-8 !w-8" aria-label="Hapus {{ $s->name }}" style="color: rgb(var(--t-rose-ink))"><x-icon name="trash" size="h-4 w-4" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad pt-2">{{ $siswa->links() }}</div>
    @endif
</div>
@endsection
