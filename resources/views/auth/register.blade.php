@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold">Buat akun siswa</h1>
        <p class="mt-2 text-sm text-ink-2">Isi data sesuai identitas di sekolah. Petugas TU dapat memverifikasi data Anda saat pengambilan dokumen.</p>
    </div>

    @include('partials.form-errors')

    <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate>
        @csrf
        <x-input name="name" label="Nama lengkap" required autocomplete="name" placeholder="Sesuai data sekolah" />
        <x-input name="email" type="email" label="Email" required autocomplete="email" placeholder="nama@email.com" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input name="nis_nip" label="NIS" required placeholder="Nomor Induk Siswa" inputmode="numeric" />
            <x-input name="kelas" label="Kelas / angkatan" required placeholder="mis. XII RPL 1" />
        </div>
        <x-input name="no_telp" type="tel" label="Nomor telepon" autocomplete="tel" placeholder="08xxxxxxxxxx" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input name="password" type="password" label="Kata sandi" required autocomplete="new-password" hint="Minimal 8 karakter, kombinasi huruf dan angka." />
            <x-input name="password_confirmation" type="password" label="Ulangi kata sandi" required autocomplete="new-password" />
        </div>
        <button type="submit" class="btn btn-primary btn-block !min-h-[3rem] text-base">Daftar sekarang</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-2">Sudah punya akun? <a href="{{ route('login') }}" class="link">Masuk</a></p>
@endsection
