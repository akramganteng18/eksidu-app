@extends('layouts.app')
@section('title', $pengumuman->exists ? 'Edit Pengumuman' : 'Buat Pengumuman')
@section('content')
<x-page-header eyebrow="Informasi sekolah" :title="$pengumuman->exists ? 'Edit pengumuman' : 'Buat pengumuman'" subtitle="Tentukan sasaran dan waktu tayang. Draft tidak terlihat oleh pengguna.">
    <a href="{{ route('admin.pengumuman.index') }}" class="btn btn-secondary">Kembali</a>
</x-page-header>
<form method="POST" action="{{ $pengumuman->exists ? route('admin.pengumuman.update', $pengumuman) : route('admin.pengumuman.store') }}" class="card card-pad mx-auto max-w-3xl space-y-5">
    @csrf
    @if($pengumuman->exists) @method('PUT') @endif
    <div><label class="label" for="judul">Judul</label><input class="input" id="judul" name="judul" maxlength="180" value="{{ old('judul', $pengumuman->judul) }}" required></div>
    <div><label class="label" for="isi">Isi pengumuman</label><textarea class="textarea min-h-40" id="isi" name="isi" maxlength="20000" required>{{ old('isi', $pengumuman->isi) }}</textarea></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="sasaran">Ditujukan kepada</label><select class="input" id="sasaran" name="sasaran" required>@foreach(['semua'=>'Semua pengguna','siswa'=>'Siswa','admin'=>'Admin/TU'] as $key=>$label)<option value="{{ $key }}" @selected(old('sasaran', $pengumuman->sasaran ?: 'semua')===$key)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="label" for="mulai_pada">Mulai ditampilkan (opsional)</label><input class="input" id="mulai_pada" name="mulai_pada" type="datetime-local" value="{{ old('mulai_pada', $pengumuman->mulai_pada?->format('Y-m-d\TH:i')) }}"></div>
        <div><label class="label" for="berakhir_pada">Berakhir ditampilkan (opsional)</label><input class="input" id="berakhir_pada" name="berakhir_pada" type="datetime-local" value="{{ old('berakhir_pada', $pengumuman->berakhir_pada?->format('Y-m-d\TH:i')) }}"></div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="diterbitkan" value="1" @checked(old('diterbitkan', $pengumuman->diterbitkan))> Terbitkan sekarang sesuai jadwal</label>
    <div class="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end" style="border-color:rgb(var(--c-line))"><a class="btn btn-secondary" href="{{ route('admin.pengumuman.index') }}">Batal</a><button class="btn btn-primary" type="submit">Simpan pengumuman</button></div>
</form>
@endsection
