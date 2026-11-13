<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf8f1">
    <title>@yield('judul') — {{ config('eksidu.nama') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-bg">
    <main class="flex min-h-screen flex-col px-5 py-5">
        <div class="mx-auto flex w-full max-w-5xl items-center justify-between">
            <a href="{{ url('/') }}" aria-label="{{ config('eksidu.nama') }}"><x-logo :size="38" /></a>
            <x-theme-toggle />
        </div>

        <div class="page-enter mx-auto flex w-full max-w-xl flex-1 flex-col items-center justify-center py-10 text-center">
            <x-illustration :name="trim($__env->yieldContent('art', 'docs'))" class="h-44 w-auto" />
            <p class="mt-6 font-display text-6xl font-extrabold tracking-tight text-brand">@yield('kode')</p>
            <h1 class="mt-3 text-2xl font-extrabold sm:text-3xl">@yield('judul')</h1>
            <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-2">@yield('pesan')</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ url('/') }}" class="btn btn-primary"><x-icon name="home" size="h-[18px] w-[18px]" /> Ke beranda</a>
                <button type="button" class="btn btn-secondary" onclick="history.length > 1 ? history.back() : (location.href = '{{ url('/') }}')"><x-icon name="arrow-left" size="h-[18px] w-[18px]" /> Kembali</button>
            </div>
        </div>

        <p class="pb-2 text-center text-xs text-muted">© {{ date('Y') }} {{ config('eksidu.nama') }} · {{ config('eksidu.sekolah') }}</p>
    </main>
</body>
</html>
