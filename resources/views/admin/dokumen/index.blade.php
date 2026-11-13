@extends('layouts.app')

@section('title', 'Jenis Dokumen')

@section('content')
<div x-data="{ modal: @js(old('_modal')), edit: @js(old('_modal') === 'edit' ? ['id' => old('_id'), 'kode' => old('kode'), 'nama' => old('nama'), 'keterangan' => old('keterangan'), 'aktif' => (bool) old('aktif'), 'layanan' => (array) old('layanan', [])] : ['id' => null, 'kode' => '', 'nama' => '', 'keterangan' => '', 'aktif' => true, 'layanan' => []]) }"
     @keydown.escape.window="modal = null">

    <x-page-header eyebrow="Data master" title="Jenis dokumen" subtitle="Daftar dokumen akademik yang dapat diajukan siswa.">
        <button type="button" class="btn btn-primary" @click="modal = 'tambah'"><x-icon name="plus" size="h-4 w-4" /> Tambah jenis dokumen</button>
    </x-page-header>

    <div class="card overflow-hidden">
        @if ($dokumen->isEmpty())
            <x-empty-state art="docs" title="Belum ada jenis dokumen" text="Tambahkan jenis dokumen agar siswa dapat memilihnya saat mengajukan layanan.">
                <button type="button" class="btn btn-primary btn-sm" @click="modal = 'tambah'"><x-icon name="plus" size="h-4 w-4" /> Tambah jenis dokumen</button>
            </x-empty-state>
        @else
            <div class="table-wrap">
                <table class="tbl">
                    <caption class="sr-only">Daftar jenis dokumen</caption>
                    <thead><tr><th scope="col">Kode</th><th scope="col">Nama dokumen</th><th scope="col">Layanan tersedia</th><th scope="col">Status</th><th scope="col" class="text-right">Dipakai</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($dokumen as $d)
                            <tr>
                                <td><span class="badge badge-plain tone-violet font-mono">{{ $d->kode }}</span></td>
                                <td><p class="font-semibold">{{ $d->nama }}</p>@if ($d->keterangan)<p class="max-w-md truncate text-xs text-muted">{{ $d->keterangan }}</p>@endif</td>
                                <td><div class="flex flex-wrap gap-1">@forelse (($d->layanan ?? []) as $kodeLayanan)<span class="badge badge-plain">{{ $layananOptions[$kodeLayanan] ?? $kodeLayanan }}</span>@empty<span class="text-xs text-muted">Tidak ditautkan ke layanan</span>@endforelse</div></td>
                                <td><x-badge :tone="$d->aktif ? 'green' : 'slate'">{{ $d->aktif ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                                <td class="num text-right font-semibold">{{ $d->pengajuans_count + $d->documents_count }}</td>
                                <td>
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                                @click="edit = @js(['id' => $d->id, 'kode' => $d->kode, 'nama' => $d->nama, 'keterangan' => $d->keterangan, 'aktif' => (bool) $d->aktif, 'layanan' => $d->layanan ?? []]); modal = 'edit'">
                                            <x-icon name="pencil" size="h-3.5 w-3.5" /> Ubah</button>
                                        <form method="POST" action="{{ route('admin.dokumen.destroy', $d) }}"
                                              data-confirm="Hapus jenis dokumen {{ $d->nama }}?" data-confirm-title="Hapus jenis dokumen" data-confirm-tone="danger" data-confirm-action="Ya, hapus">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-secondary btn-sm btn-icon !h-8 !w-8" aria-label="Hapus {{ $d->nama }}" style="color: rgb(var(--t-rose-ink))"><x-icon name="trash" size="h-4 w-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-pad pt-2">{{ $dokumen->links() }}</div>
        @endif
    </div>

    {{-- Modal tambah --}}
    <div x-show="modal === 'tambah'" x-cloak x-transition.opacity class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="judul-tambah">
        <form method="POST" action="{{ route('admin.dokumen.store') }}" class="modal space-y-4" @click.outside="modal = null" novalidate>
            @csrf <input type="hidden" name="_modal" value="tambah">
            <h2 id="judul-tambah" class="text-lg font-extrabold">Tambah jenis dokumen</h2>
            <x-input name="kode" label="Kode" required maxlength="20" placeholder="mis. IJZ" hint="Singkat & unik. Otomatis huruf kapital."  id="tambah_kode" scope="tambah" />
            <x-input name="nama" label="Nama dokumen" required id="tambah_nama" scope="tambah" />
            <x-textarea name="keterangan" label="Keterangan" rows="3" id="tambah_ket" scope="tambah" />
            <fieldset class="space-y-2">
                <legend class="label">Tersedia untuk layanan</legend>
                <p class="hint">Pilih layanan yang boleh menggunakan jenis dokumen ini. Kosongkan semua pilihan jika dokumen belum tersedia untuk pengajuan mana pun.</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($layananOptions as $kodeLayanan => $labelLayanan)
                        <label class="flex items-center gap-2 rounded-xl p-2 text-sm" style="background: rgb(var(--c-surface-2))">
                            <input type="checkbox" name="layanan[]" value="{{ $kodeLayanan }}" class="h-4 w-4 rounded" @checked(in_array($kodeLayanan, (array) old('layanan', []), true))>
                            {{ $labelLayanan }}
                        </label>
                    @endforeach
                </div>
                @error('layanan')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-secondary" @click="modal = null">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>

    {{-- Modal ubah --}}
    <div x-show="modal === 'edit'" x-cloak x-transition.opacity class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="judul-edit">
        <form method="POST" :action="@js(route('admin.dokumen.update', '__ID__')).replace('__ID__', edit.id)" class="modal space-y-4" @click.outside="modal = null" novalidate>
            @csrf @method('PUT') <input type="hidden" name="_modal" value="edit"><input type="hidden" name="_id" :value="edit.id">
            <h2 id="judul-edit" class="text-lg font-extrabold">Ubah jenis dokumen</h2>
            @if ($errors->any() && old('_modal') === 'edit')
                <div class="alert tone-rose" role="alert"><x-icon name="warning" size="mt-0.5 h-5 w-5" /><ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div><label for="edit_kode" class="label">Kode</label><input id="edit_kode" name="kode" class="input" maxlength="20" required x-model="edit.kode"></div>
            <div><label for="edit_nama" class="label">Nama dokumen</label><input id="edit_nama" name="nama" class="input" required x-model="edit.nama"></div>
            <div><label for="edit_ket" class="label">Keterangan</label><textarea id="edit_ket" name="keterangan" rows="3" class="textarea" x-model="edit.keterangan"></textarea></div>
            <fieldset class="space-y-2">
                <legend class="label">Tersedia untuk layanan</legend>
                <p class="hint">Pilihan di formulir siswa akan mengikuti layanan yang dicentang. Kosongkan semua pilihan untuk menyembunyikan jenis ini dari semua layanan.</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($layananOptions as $kodeLayanan => $labelLayanan)
                        <label class="flex items-center gap-2 rounded-xl p-2 text-sm" style="background: rgb(var(--c-surface-2))">
                            <input type="checkbox" name="layanan[]" value="{{ $kodeLayanan }}" class="h-4 w-4 rounded" x-model="edit.layanan">
                            {{ $labelLayanan }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <label class="flex cursor-pointer items-center gap-2.5 text-sm font-semibold">
                <input type="hidden" name="aktif" value="0"><input type="checkbox" name="aktif" value="1" class="h-4 w-4 rounded" x-model="edit.aktif"> Aktif (tampil di formulir pengajuan)
            </label>
            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-secondary" @click="modal = null">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
