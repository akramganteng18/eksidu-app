<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fbf8f1">
    <meta name="description" content="{{ config('eksidu.nama') }} — {{ config('eksidu.nama_panjang') }} {{ config('eksidu.sekolah') }}.">
    <title>@yield('title', 'Dashboard') — {{ config('eksidu.nama') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="app-bg" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false"
      x-effect="document.body.classList.toggle('max-lg:overflow-hidden', sidebarOpen)">
    <a href="#konten" class="skip-link">Lewati ke konten utama</a>
    <div id="top-progress" aria-hidden="true"></div>

    <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/55 backdrop-blur-[2px] lg:hidden" aria-hidden="true"></div>

    <x-sidebar />

    <div class="lg:pl-[16.5rem]">
        <x-navbar />

        <main id="konten" tabindex="-1" class="page-enter mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @include('partials.form-errors')
            @yield('content')
        </main>

        <footer class="no-print mx-auto max-w-7xl px-4 pb-8 pt-2 text-xs text-muted sm:px-6 lg:px-8">
            <div class="flex flex-col gap-1 pt-4 sm:flex-row sm:items-center sm:justify-between" style="border-top: 1px solid rgb(var(--c-line))">
                <p>© {{ date('Y') }} <span class="font-bold text-ink-2">{{ config('eksidu.nama') }}</span> · {{ config('eksidu.sekolah') }}</p>
                <p>{{ config('eksidu.tagline') }}</p>
            </div>
        </footer>
    </div>

    @include('partials.toasts')
    @include('partials.confirm-dialog')
    @stack('scripts')
</body>
</html>
