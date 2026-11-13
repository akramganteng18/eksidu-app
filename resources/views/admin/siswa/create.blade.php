@extends('layouts.app')

@section('title', 'Tambah Siswa')

@section('content')
<x-page-header eyebrow="Data master" title="Tambah siswa" subtitle="Buat akun baru untuk siswa atau alumni. Berikan kata sandi awal kepada siswa dan minta mereka mengubahnya.">
    <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="h-4 w-4" /> Kembali</a>
</x-page-header>

<form method="POST" action="{{ route('admin.siswa.store') }}" class="card card-pad mx-auto max-w-3xl space-y-5" novalidate>
    @csrf
    <div class="grid gap-5 sm:grid-cols-2">
        <x-input name="name" label="Nama lengkap" required class="sm:col-span-2" autocomplete="off" />
        <x-input name="email" type="email" label="Email" required autocomplete="off" hint="Dipakai untuk masuk ke aplikasi." />
        <x-input name="nis_nip" label="NIS" inputmode="numeric" hint="Harus unik." />
        <x-input name="kelas" label="Kelas / angkatan" placeholder="mis. XII RPL 1" />
        <x-input name="no_telp" type="tel" label="Nomor telepon" placeholder="08xxxxxxxxxx" />
        <x-input name="password" type="password" label="Kata sandi awal" required autocomplete="new-password" class="sm:col-span-2" hint="Minimal 8 karakter, kombinasi huruf dan angka." />
    </div>
    <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end" style="border-color: rgb(var(--c-line))">
        <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary"><x-icon name="check" size="h-4 w-4" /> Simpan siswa</button>
    </div>
</form>
@endsection
