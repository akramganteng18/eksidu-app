<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fbf8f1">
    <meta name="description" content="{{ config('eksidu.nama') }} — {{ config('eksidu.nama_panjang') }} {{ config('eksidu.sekolah') }}.">
    <title>@yield('title', 'Masuk') — {{ config('eksidu.nama') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-bg">
    <a href="#konten" class="skip-link">Lewati ke formulir</a>
    <div id="top-progress" aria-hidden="true"></div>

    <div class="grid min-h-screen lg:grid-cols-[1.08fr_1fr]">
        {{-- Panel visual (desktop) --}}
        <aside class="auth-visual hidden flex-col justify-between gap-10 p-10 lg:flex xl:p-14" aria-hidden="false">
            <x-logo :size="44" hero />

            <div class="max-w-lg">
                <p class="mb-3 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold" style="background: rgb(255 255 255 / .16)">
                    <x-icon name="sparkles" size="h-3.5 w-3.5" /> Layanan dokumen akademik daring
                </p>
                <h2 class="text-4xl font-extrabold leading-[1.1]">Urus dokumen sekolah,<br>tanpa antre berlama-lama.</h2>
                <p class="mt-4 max-w-md text-base opacity-90">{{ config('eksidu.nama_panjang') }} untuk siswa dan alumni {{ config('eksidu.sekolah') }}.</p>

                <ul class="mt-8 space-y-3.5">
                    <li class="auth-point"><span class="chip"><x-icon name="clipboard-check" size="h-4 w-4" /></span><span>Ajukan peminjaman rapor, legalisir, dan pengambilan ijazah dari rumah.</span></li>
                    <li class="auth-point"><span class="chip"><x-icon name="search" size="h-4 w-4" /></span><span>Pantau status lewat nomor tiket, lengkap dengan riwayat prosesnya.</span></li>
                    <li class="auth-point"><span class="chip"><x-icon name="qr" size="h-4 w-4" /></span><span>Ambil dokumen cukup dengan menunjukkan QR Code ke petugas TU.</span></li>
                </ul>
            </div>

            <div class="flex items-end justify-between gap-6">
                <p class="text-xs opacity-80">© {{ date('Y') }} {{ config('eksidu.nama') }} · {{ config('eksidu.sekolah') }}</p>
                <x-illustration name="hero" class="-mb-2 h-32 w-auto xl:h-40" />
            </div>
        </aside>

        {{-- Formulir --}}
        <main id="konten" tabindex="-1" class="relative flex min-h-screen flex-col px-5 py-5 sm:px-10">
            <div class="flex items-center justify-between">
                <a href="{{ route('login') }}" class="lg:hidden" aria-label="{{ config('eksidu.nama') }}"><x-logo :size="38" /></a>
                <span class="ml-auto"><x-theme-toggle /></span>
            </div>

            <div class="page-enter mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-8">
                @yield('content')
            </div>

            <p class="pb-2 text-center text-xs text-muted lg:hidden">© {{ date('Y') }} {{ config('eksidu.nama') }} · {{ config('eksidu.sekolah_singkat') }}</p>
        </main>
    </div>

    @include('partials.toasts')
</body>
</html>
