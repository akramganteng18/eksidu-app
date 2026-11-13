@php
    $user = auth()->user();
    $admin = $user->isAdmin();

    $menu = $admin ? [
        ['judul' => 'Utama', 'item' => [
            ['Dashboard', 'dashboard', 'dashboard', 'home', 'teal'],
        ]],
        ['judul' => 'Layanan Dokumen', 'item' => [
            ['Pengajuan Masuk', 'admin.pengajuan.index', 'admin.pengajuan.*', 'clipboard-check', 'violet'],
            ['Arsip Dokumen Siswa', 'admin.arsip-siswa.index', 'admin.arsip-siswa.*', 'archive', 'teal'],
            ['Scan QR Pengambilan', 'admin.scan-qr', 'admin.scan-qr*', 'qr', 'sky'],
        ]],
        ['judul' => 'Data Master', 'item' => [
            ['Data Siswa', 'admin.siswa.index', 'admin.siswa.*', 'users', 'coral'],
            ['Jenis Dokumen', 'admin.dokumen.index', 'admin.dokumen.*', 'folder', 'amber'],
        ]],
        ['judul' => 'Laporan & Sistem', 'item' => [
            ['Pengumuman Sekolah', 'admin.pengumuman.index', 'admin.pengumuman.*', 'bell', 'violet'],
            ['Keamanan Akun', 'admin.security.two-factor', 'admin.security.*', 'shield', 'slate'],
            ['Laporan', 'admin.laporan.index', 'admin.laporan.*', 'chart', 'green'],
            ['Log Aktivitas', 'admin.aktivitas.index', 'admin.aktivitas.*', 'clock', 'slate'],
        ]],
        ['judul' => 'Bantuan', 'item' => [
            ['Pusat Panduan', 'panduan.index', 'panduan.*', 'video', 'violet'],
            ['Kelola Panduan', 'admin.panduan.index', 'admin.panduan.*', 'sparkles', 'teal'],
        ]],
    ] : [
        ['judul' => 'Utama', 'item' => [
            ['Dashboard', 'dashboard', 'dashboard', 'home', 'teal'],
        ]],
        ['judul' => 'Layanan Saya', 'item' => [
            ['Ajukan Layanan', 'siswa.pengajuan.create', 'siswa.pengajuan.create', 'plus', 'violet'],
            ['Dokumen Saya', 'siswa.dokumen-saya.index', 'siswa.dokumen-saya.*', 'folder-open', 'teal'],
            ['Riwayat Pengajuan', 'siswa.pengajuan.index', 'siswa.pengajuan.index|siswa.pengajuan.show', 'clipboard-check', 'sky'],
            ['Lacak Tiket', 'siswa.pengajuan.lacak.form', 'siswa.pengajuan.lacak.*', 'search', 'amber'],
        ]],
        ['judul' => 'Bantuan', 'item' => [
            ['Pusat Panduan', 'panduan.index', 'panduan.*', 'video', 'coral'],
        ]],
    ];

    $aktif = function (string $pola) {
        foreach (explode('|', $pola) as $p) {
            if (request()->routeIs($p)) return true;
        }
        return false;
    };
@endphp
<aside id="sidebar" class="sidebar" :data-open="sidebarOpen" aria-label="Navigasi utama">
    <div class="flex h-16 shrink-0 items-center justify-between px-4" style="border-bottom: 1px solid rgb(var(--c-line))">
        <a href="{{ route('dashboard') }}" aria-label="{{ config('eksidu.nama') }} — ke dashboard"><x-logo :size="36" /></a>
        <button type="button" class="btn btn-ghost btn-icon lg:hidden" @click="sidebarOpen = false" aria-label="Tutup menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-4" x-ref="sidenav">
        @foreach ($menu as $grup)
            <p class="nav-group">{{ $grup['judul'] }}</p>
            <ul class="space-y-1">
                @foreach ($grup['item'] as [$label, $rute, $pola, $ikon, $tone])
                    <li>
                        <a href="{{ route($rute) }}" class="nav-link tone-{{ $tone }}"
                           @if ($aktif($pola)) aria-current="page" @endif>
                            <span class="nav-ico"><x-icon :name="$ikon" size="h-[18px] w-[18px]" /></span>
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="shrink-0 space-y-3 p-3" style="border-top: 1px solid rgb(var(--c-line))">
        <div class="flex items-center gap-2.5 rounded-xl px-2.5 py-2" style="background: rgb(var(--c-surface-2))">
            <span class="chip tone-teal h-9 w-9"><x-icon name="calendar" size="h-[18px] w-[18px]" /></span>
            <div class="min-w-0 leading-tight">
                <p class="text-[11px] font-semibold text-muted">Tahun Ajaran</p>
                <p class="text-sm font-bold">{{ \App\Support\Akademik::tahunAjaran() }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link tone-rose w-full">
                <span class="nav-ico"><x-icon name="logout" size="h-[18px] w-[18px]" /></span>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
