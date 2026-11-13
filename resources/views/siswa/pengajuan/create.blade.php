@extends('layouts.app')
@section('title', 'Ajukan Layanan')
@section('content')
<x-page-header eyebrow="Layanan saya" title="Ajukan Layanan" subtitle="Pilih dokumen dari arsip pribadi yang tersedia. Semua permintaan diproses melalui halaman ini.">
    <a href="{{ route('siswa.pengajuan.index') }}" class="btn btn-secondary">Riwayat Pengajuan</a>
</x-page-header>
@php
    $archiveData = $archives->all();
@endphp
<div class="card card-pad max-w-3xl" x-data="{
    service: @js(old('kategori_layanan', '')),
    docCategory: @js(old('kategori_dokumen', '')),
    form: @js(old('bentuk_dokumen', '')),
    archiveId: @js(old('archive_id', '')),
    semester: @js(old('semester_akademik', '')),
    period: @js(old('periode_rapor', '')),
    archives: @js($archiveData),
    get category() { return this.service === 'legalisir' ? this.docCategory : this.service; },
    get choices() { return this.archives.filter(a => a.category === this.category && (this.form === 'digital' ? a.digital : this.form === 'fisik' ? a.physical : true) && (this.category !== 'rapor' || ((!this.semester || String(a.semester) === String(this.semester)) && (!this.period || a.period === this.period)))); },
    chooseService() { this.archiveId=''; this.form=''; this.docCategory=''; this.semester=''; this.period=''; },
    chooseForm() { this.archiveId=''; }
}">
<form method="POST" action="{{ route('siswa.pengajuan.store') }}" enctype="multipart/form-data" class="space-y-6">
@csrf
<div><label class="label">1. Pilih layanan</label><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
@foreach(['rapor'=>'Rapor','tka'=>'TKA','skls'=>'SKLS','ijazah'=>'Ijazah','legalisir'=>'Legalisir'] as $key=>$label)
<label class="card card-pad cursor-pointer" :class="service==='{{ $key }}' ? 'ring-2 ring-brand' : ''"><input type="radio" name="kategori_layanan" value="{{ $key }}" x-model="service" @change="chooseService()" required><span class="ml-2 font-semibold">{{ $label }}</span></label>
@endforeach
</div>@error('kategori_layanan')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
<div x-show="service==='legalisir'" x-cloak><label class="label">Kategori dokumen yang dilegalisir</label><select name="kategori_dokumen" x-model="docCategory" @change="archiveId='';form=''" class="input w-full"><option value="">Pilih kategori</option>@foreach(['rapor'=>'Rapor','tka'=>'TKA','skls'=>'SKLS','ijazah'=>'Ijazah'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
<div x-show="category==='rapor'" x-cloak class="grid gap-4 sm:grid-cols-2"><div><label class="label">Semester akademik</label><select x-model="semester" name="semester_akademik" class="input w-full" :required="category==='rapor'" @change="archiveId=''"><option value="">Semua semester</option><option value="1">Semester 1</option><option value="2">Semester 2</option></select></div><div><label class="label">Periode rapor</label><select x-model="period" name="periode_rapor" class="input w-full" :required="category==='rapor'" @change="archiveId=''"><option value="">Semua periode</option><option value="tengah">Tengah Semester</option><option value="akhir">Akhir Semester</option></select></div></div>
<div><label class="label">2. Bentuk dokumen</label><div class="flex flex-wrap gap-3"><label class="btn btn-secondary"><input type="radio" name="bentuk_dokumen" value="fisik" x-model="form" @change="chooseForm()"> <span>Dokumen Fisik</span></label><label class="btn btn-secondary"><input type="radio" name="bentuk_dokumen" value="digital" x-model="form" @change="chooseForm()"> <span>Dokumen Digital</span></label></div>@error('bentuk_dokumen')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
<div><label class="label">3. Pilih arsip yang tersedia</label><select name="archive_id" x-model="archiveId" class="input w-full" required><option value="">Pilih dokumen dari arsip Anda</option><template x-for="item in choices" :key="item.id"><option :value="item.id" x-text="item.category_label+' — '+item.detail"></option></template></select><p class="text-xs text-muted mt-1" x-show="service && form && choices.length===0">Belum ada arsip yang tersedia untuk kombinasi pilihan tersebut. Hubungi Tata Usaha.</p>@error('archive_id')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
<div x-show="service==='legalisir'" x-cloak><label class="label">Jumlah lembar legalisir</label><input type="number" name="jumlah_lembar" min="1" max="20" value="{{ old('jumlah_lembar',1) }}" class="input w-full"></div>
<div><label class="label">Keperluan</label><textarea name="keperluan" rows="3" class="input w-full" required>{{ old('keperluan') }}</textarea>@error('keperluan')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror</div>
<div><label class="label">Berkas pendukung (opsional)</label><input type="file" name="file_pendukung" accept=".pdf,.jpg,.jpeg,.png" class="input w-full"></div>
<button class="btn btn-primary" type="submit" :disabled="!service || !form || !archiveId">Kirim Pengajuan</button>
</form></div>
@endsection
