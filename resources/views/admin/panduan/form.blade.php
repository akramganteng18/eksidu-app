@extends('layouts.app')

@php $ubah = $panduan->exists; @endphp

@section('title', $ubah ? 'Ubah Materi Panduan' : 'Tambah Materi Panduan')

@section('content')
<x-page-header eyebrow="Bantuan" :title="$ubah ? 'Ubah materi panduan' : 'Tambah materi panduan'"
               subtitle="Video & audio diputar lewat pemutar bawaan dan hanya dapat diakses pengguna yang berhak.">
    <a href="{{ route('admin.panduan.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="h-4 w-4" /> Kembali</a>
</x-page-header>

<form method="POST" enctype="multipart/form-data" novalidate class="card card-pad mx-auto max-w-3xl space-y-5"
      action="{{ $ubah ? route('admin.panduan.update', $panduan) : route('admin.panduan.store') }}"
      x-data="{ sumber: @js(old('sumber', $panduan->sumber)), jenis: @js(old('jenis', $panduan->jenis)) }"
      x-effect="if (sumber === 'tautan') jenis = 'video'">
    @csrf
    @if ($ubah) @method('PUT') @endif

    <x-input name="judul" label="Judul materi" required :value="$panduan->judul" placeholder="mis. Cara mengajukan legalisir ijazah" />
    <x-textarea name="deskripsi" label="Deskripsi singkat" rows="3" :value="$panduan->deskripsi" />

    <fieldset>
        <legend class="label">Sumber media <span class="text-[rgb(var(--t-rose-ink))]" aria-hidden="true">*</span></legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (\App\Models\PanduanMedia::SUMBER as $kode => $label)
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl p-3.5 transition" style="border: 1px solid rgb(var(--c-line-strong))"
                       :class="sumber === '{{ $kode }}' ? 'ring-2 ring-brand bg-brand-soft' : 'hover:bg-surface-2'">
                    <input type="radio" name="sumber" value="{{ $kode }}" x-model="sumber" class="h-4 w-4">
                    <span class="text-sm font-semibold">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-select name="jenis" label="Jenis media" required :options="\App\Models\PanduanMedia::JENIS" :selected="$panduan->jenis" x-model="jenis"
                  hint="Tautan YouTube selalu bertipe video." />
        <x-select name="untuk" label="Sasaran pengguna" required :options="\App\Models\PanduanMedia::UNTUK" :selected="$panduan->untuk" />
    </div>

    {{-- Unggah berkas --}}
    <div x-show="sumber === 'unggah'" x-data="fileField({{ config('eksidu.batas_media_kb') }})">
        <label for="berkas" class="label">Berkas media @unless ($ubah && $panduan->file_path)<span class="text-[rgb(var(--t-rose-ink))]" aria-hidden="true">*</span>@endunless</label>
        <input id="berkas" name="berkas" type="file" class="input" @change="pilih($event)"
               :accept="jenis === 'audio' ? '.mp3,.wav,.ogg,.m4a' : '.mp4,.webm'"
               @error('berkas') aria-invalid="true" aria-describedby="berkas-error" @enderror>
        <p class="hint" x-show="jenis === 'video'">Video: MP4 atau WebM, maks. {{ round(config('eksidu.batas_media_kb') / 1024) }} MB.</p>
        <p class="hint" x-show="jenis === 'audio'" x-cloak>Audio: MP3, WAV, OGG, atau M4A, maks. {{ round(config('eksidu.batas_media_kb') / 1024) }} MB.</p>
        <p class="hint">Pastikan <code>upload_max_filesize</code> dan <code>post_max_size</code> di php.ini cukup besar.</p>
        @if ($ubah && $panduan->file_path)<p class="hint flex items-center gap-1.5"><x-icon name="paperclip" size="h-3.5 w-3.5" /> Berkas saat ini tersimpan. Pilih berkas baru hanya jika ingin menggantinya.</p>@endif
        <p class="field-error" x-show="galat" x-cloak x-text="galat"></p>
        @error('berkas')<p id="berkas-error" class="field-error"><x-icon name="warning" size="mt-px h-3.5 w-3.5" />{{ $message }}</p>@enderror
    </div>

    {{-- Tautan YouTube --}}
    <div x-show="sumber === 'tautan'" x-cloak>
        <x-input name="url" label="Tautan YouTube" type="url" :value="$panduan->url" placeholder="https://www.youtube.com/watch?v=..." hint="Hanya tautan youtube.com atau youtu.be yang diterima. Video diputar lewat youtube-nocookie.com." />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-input name="urutan" type="number" label="Urutan tampil" min="0" max="999" :value="$panduan->urutan" hint="Angka kecil tampil lebih dulu." />
        <label class="flex cursor-pointer items-center gap-3 self-end rounded-2xl p-3.5" style="background: rgb(var(--c-surface-2)); border: 1px solid rgb(var(--c-line))">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" value="1" class="h-4 w-4 rounded" @checked(old('aktif', $panduan->aktif ?? true))>
            <span class="text-sm font-semibold">Tampilkan kepada pengguna</span>
        </label>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: rgb(var(--c-line))">
        <a href="{{ route('admin.panduan.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary"><x-icon name="check" size="h-4 w-4" /> {{ $ubah ? 'Simpan perubahan' : 'Simpan materi' }}</button>
    </div>
</form>
@endsection
