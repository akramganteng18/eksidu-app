@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold">Selamat datang kembali</h1>
        <p class="mt-2 text-sm text-ink-2">Masuk untuk mengelola dan memantau layanan dokumen akademik Anda.</p>
    </div>

    @if (session('status'))
        <div class="alert tone-green mb-5" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert tone-rose mb-5" role="alert">
            <x-icon name="warning" size="mt-0.5 h-5 w-5" />
            <p>{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate
          x-data="{ lihat: false }">
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <div class="input-icon">
                <x-icon name="user-circle" size="h-[18px] w-[18px]" class="icon-left" />
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="input"
                       placeholder="nama@email.com" autocomplete="username" autofocus required
                       @if ($errors->has('email')) aria-invalid="true" @endif>
            </div>
        </div>

        <div>
            <label for="password" class="label">Kata sandi</label>
            <div class="input-icon">
                <x-icon name="lock" size="h-[18px] w-[18px]" class="icon-left" />
                <input id="password" name="password" :type="lihat ? 'text' : 'password'" type="password" class="input pr-12"
                       placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                <button type="button" class="btn btn-ghost btn-icon icon-right !h-9 !w-9" @click="lihat = !lihat"
                        :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="lihat">
                    <span x-show="!lihat"><x-icon name="eye" size="h-[18px] w-[18px]" /></span>
                    <span x-show="lihat" x-cloak><x-icon name="eye-off" size="h-[18px] w-[18px]" /></span>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-2">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded"> Ingat saya di perangkat ini
            </label>
            <a class="link text-sm" href="{{ route('password.request') }}">Lupa kata sandi?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-block !min-h-[3rem] text-base">
            Masuk <x-icon name="arrow-right" size="h-5 w-5" />
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-ink-2">
        @if (Route::has('register'))
            Belum punya akun? <a href="{{ route('register') }}" class="link">Daftar sebagai siswa</a>
        @else
            Belum punya akun? Akun siswa dibuat oleh petugas TU sekolah.
        @endif
    </div>

    @if (config('eksidu.akun_demo'))
        <div class="card mt-8 p-4 text-sm" x-data>
            <p class="mb-2 flex items-center gap-2 font-bold"><x-icon name="info" size="h-4 w-4" /> Akun demo <span class="font-medium text-muted">(khusus lingkungan pengembangan)</span></p>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary btn-sm"
                        @click="document.getElementById('email').value = @js(config('eksidu.admin.email')); document.getElementById('password').value = @js(config('eksidu.demo_password'))">
                    Isi sebagai Admin
                </button>
                <button type="button" class="btn btn-secondary btn-sm"
                        @click="document.getElementById('email').value = 'ahmad.fauzan@siswa.smkn5surakarta.sch.id'; document.getElementById('password').value = @js(config('eksidu.demo_password'))">
                    Isi sebagai Siswa
                </button>
            </div>
            <p class="mt-2 text-xs text-muted">Aktif bila <code>EKSIDU_AKUN_DEMO=true</code> dan data demo sudah di-seed. Matikan di produksi.</p>
        </div>
    @endif
@endsection
