@extends('layouts.app')

@section('title', 'Kelola Panduan')

@section('content')
<x-page-header eyebrow="Bantuan" title="Kelola materi panduan" subtitle="Unggah video atau audio panduan, atau tautkan video YouTube. Atur siapa yang boleh melihatnya.">
    <a href="{{ route('panduan.index') }}" class="btn btn-secondary"><x-icon name="eye" size="h-4 w-4" /> Lihat sebagai pengguna</a>
    <a href="{{ route('admin.panduan.create') }}" class="btn btn-primary"><x-icon name="plus" size="h-4 w-4" /> Tambah materi</a>
</x-page-header>

<div class="card overflow-hidden">
    @if ($daftar->isEmpty())
        <x-empty-state art="media" title="Belum ada materi panduan" text="Tambahkan video atau audio agar siswa dan petugas lebih mudah memakai aplikasi.">
            <a href="{{ route('admin.panduan.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="h-4 w-4" /> Tambah materi pertama</a>
        </x-empty-state>
    @else
        <div class="table-wrap">
            <table class="tbl">
                <caption class="sr-only">Daftar materi panduan</caption>
                <thead><tr><th scope="col">Materi</th><th scope="col">Jenis</th><th scope="col">Sumber</th><th scope="col">Sasaran</th><th scope="col">Status</th><th scope="col" class="text-right">Urutan</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach ($daftar as $m)
                        <tr>
                            <td><div class="flex items-center gap-3">
                                <span class="chip {{ $m->isVideo() ? 'tone-violet' : 'tone-coral' }} h-10 w-10 shrink-0"><x-icon :name="$m->isVideo() ? 'video' : 'audio'" size="h-5 w-5" /></span>
                                <div class="min-w-0"><p class="truncate font-semibold">{{ $m->judul }}</p>@if ($m->deskripsi)<p class="max-w-xs truncate text-xs text-muted">{{ $m->deskripsi }}</p>@endif</div></div></td>
                            <td class="text-ink-2">{{ \App\Models\PanduanMedia::JENIS[$m->jenis] }}</td>
                            <td class="text-ink-2">{{ \App\Models\PanduanMedia::SUMBER[$m->sumber] }}</td>
                            <td class="text-ink-2">{{ \App\Models\PanduanMedia::UNTUK[$m->untuk] }}</td>
                            <td><x-badge :tone="$m->aktif ? 'green' : 'slate'">{{ $m->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                            <td class="num text-right">{{ $m->urutan }}</td>
                            <td><div class="flex justify-end gap-1.5">
                                <a href="{{ route('panduan.show', $m) }}" class="btn btn-secondary btn-sm btn-icon !h-8 !w-8" aria-label="Lihat {{ $m->judul }}"><x-icon name="eye" size="h-4 w-4" /></a>
                                <a href="{{ route('admin.panduan.edit', $m) }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" size="h-3.5 w-3.5" /> Ubah</a>
                                <form method="POST" action="{{ route('admin.panduan.destroy', $m) }}"
                                      data-confirm="Hapus materi &quot;{{ $m->judul }}&quot;? Berkas yang diunggah juga akan dihapus." data-confirm-title="Hapus materi panduan" data-confirm-tone="danger" data-confirm-action="Ya, hapus">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm btn-icon !h-8 !w-8" aria-label="Hapus {{ $m->judul }}" style="color: rgb(var(--t-rose-ink))"><x-icon name="trash" size="h-4 w-4" /></button>
                                </form></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad pt-2">{{ $daftar->links() }}</div>
    @endif
</div>
@endsection
