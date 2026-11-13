@extends('layouts.app')

@section('title', 'Ubah Siswa')

@section('content')
<x-page-header eyebrow="Data master" :title="'Ubah data: ' . $siswa->name" subtitle="Perbarui data akun. Kosongkan kata sandi bila tidak ingin mengubahnya.">
    <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="h-4 w-4" /> Kembali</a>
</x-page-header>

<form method="POST" action="{{ route('admin.siswa.update', $siswa) }}" class="card card-pad mx-auto max-w-3xl space-y-5" novalidate>
    @csrf @method('PUT')
    <div class="grid gap-5 sm:grid-cols-2">
        <x-input name="name" label="Nama lengkap" required :value="$siswa->name" class="sm:col-span-2" />
        <x-input name="email" type="email" label="Email" required :value="$siswa->email" />
        <x-input name="nis_nip" label="NIS" inputmode="numeric" :value="$siswa->nis_nip" />
        <x-input name="kelas" label="Kelas / angkatan" :value="$siswa->kelas" />
        <x-input name="no_telp" type="tel" label="Nomor telepon" :value="$siswa->no_telp" />
        <x-input name="password" type="password" label="Kata sandi baru" autocomplete="new-password" class="sm:col-span-2" hint="Kosongkan bila tidak diubah. Minimal 8 karakter, kombinasi huruf dan angka." />
    </div>

    <label class="flex cursor-pointer items-start gap-3 rounded-2xl p-4" style="background: rgb(var(--c-surface-2)); border: 1px solid rgb(var(--c-line))">
        <input type="hidden" name="aktif" value="0">
        <input type="checkbox" name="aktif" value="1" class="mt-0.5 h-4 w-4 rounded" @checked(old('aktif', $siswa->aktif))>
        <span><span class="block text-sm font-bold">Akun aktif</span>
            <span class="block text-xs text-muted">Akun nonaktif tidak dapat masuk dan sesi yang sedang berjalan diputus. Riwayat pengajuan tetap tersimpan.</span></span>
    </label>

    <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: rgb(var(--c-line))">
        <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary"><x-icon name="check" size="h-4 w-4" /> Simpan perubahan</button>
    </div>
</form>
@endsection
