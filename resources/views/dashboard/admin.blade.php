@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
@php
    $user = auth()->user();
    $jam = now()->hour;
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    $namaDepan = explode(' ', trim($user->name))[0];
    $itemJenis = $perJenis->map(function ($row) {
        $kategori = \App\Models\StudentDocumentArchive::CATEGORIES[$row->kategori_layanan] ?? null;
        $label = $row->jenis_layanan === 'legalisir' ? 'Legalisir' . ($kategori ? ' ' . $kategori : '') : ($kategori ? 'Permintaan ' . $kategori : (\App\Models\Pengajuan::JENIS_SINGKAT[$row->jenis_layanan] ?? $row->jenis_layanan));
        return ['label' => $label, 'total' => (int) $row->total];
    })->values()->all();
    $adaBulanan = collect($bulanan)->sum('total') > 0;
@endphp

<div class="space-y-6">
    {{-- Hero + antrean --}}
    <section class="stagger grid gap-6 xl:grid-cols-3">
        <div class="hero p-6 sm:p-8 xl:col-span-2">
            <div class="grid items-center gap-6 md:grid-cols-[1fr_auto]">
                <div>
                    <p class="hero-muted text-sm font-semibold">{{ now()->translatedFormat('l, d F Y') }}</p>
                    <h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ $salam }}, {{ $namaDepan }}!</h1>
                    <p class="hero-muted mt-2 max-w-lg text-sm leading-relaxed">
                        Pusat kendali layanan dokumen akademik {{ config('eksidu.sekolah') }}.
                        @if ($ringkas['pending'] > 0)
                            Ada <strong class="text-[rgb(var(--hero-fg))]">{{ $ringkas['pending'] }} pengajuan</strong> menunggu verifikasi Anda.
                        @else
                            Semua pengajuan masuk sudah tertangani. Kerja bagus!
                        @endif
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2.5">
                        <a href="{{ route('admin.pengajuan.index', ['status' => 'pending']) }}" class="btn btn-hero"><x-icon name="clipboard-check" size="h-[18px] w-[18px]" /> Verifikasi pengajuan</a>
                        <a href="{{ route('admin.scan-qr') }}" class="btn btn-hero-outline"><x-icon name="qr" size="h-[18px] w-[18px]" /> Scan QR</a>
                    </div>
                </div>
                <x-illustration name="hero" class="hidden h-40 w-auto md:block lg:h-48" />
            </div>
        </div>

        <div class="card flex flex-col">
            <div class="card-head">
                <h2 class="card-title">Antrean verifikasi</h2>
                <a href="{{ route('admin.pengajuan.index', ['status' => 'pending']) }}" class="link text-xs">Lihat semua</a>
            </div>
            @forelse ($antrean as $a)
                <a href="{{ route('admin.pengajuan.show', $a) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-surface-2 sm:px-6" @if (! $loop->first) style="border-top: 1px solid rgb(var(--c-line))" @endif>
                    <x-avatar :user="$a->user" size="h-9 w-9 text-xs" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold">{{ $a->user->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $a->labelJenisLayanan() }} · {{ $a->created_at->diffForHumans() }}</p>
                    </div>
                    <x-status-badge :status="$a->status" />
                </a>
            @empty
                <x-empty-state art="success" title="Antrean kosong" text="Tidak ada pengajuan yang menunggu atau sedang diproses." class="!py-8" />
            @endforelse
        </div>
    </section>

    @if($pengumuman->isNotEmpty())
        <section class="card card-pad space-y-3" aria-labelledby="judul-pengumuman-admin">
            <div class="flex items-center justify-between gap-3"><h2 id="judul-pengumuman-admin" class="card-title flex items-center gap-2"><x-icon name="bell" size="h-4 w-4"/> Pengumuman sekolah</h2><span class="text-xs text-muted">{{ $pengumuman->count() }} informasi</span></div>
            @foreach($pengumuman as $item)
                <article class="rounded-xl p-4" style="background:rgb(var(--c-surface-2))">
                    <div class="flex flex-wrap items-center gap-2"><h3 class="text-sm font-extrabold">{{ $item->judul }}</h3><x-badge tone="sky">{{ ['semua'=>'Semua pengguna','siswa'=>'Siswa','admin'=>'Admin/TU'][$item->sasaran] ?? $item->sasaran }}</x-badge></div>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink-2">{{ $item->isi }}</p>
                </article>
            @endforeach
        </section>
    @endif

    {{-- Kartu statistik --}}
    <section class="stagger grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Ringkasan statistik">
        <x-stat-card class="col-span-2" label="Total pengajuan" :value="$ringkas['total']" tone="teal" icon="document" lime
                     :href="route('admin.pengajuan.index')" hint="Seluruh layanan sejak awal" />
        <x-stat-card label="Menunggu" :value="$ringkas['pending']" tone="amber" icon="clock" :href="route('admin.pengajuan.index', ['status' => 'pending'])" />
        <x-stat-card label="Diproses" :value="$ringkas['diproses']" tone="sky" icon="refresh" :href="route('admin.pengajuan.index', ['status' => 'diproses'])" />
        <x-stat-card label="Siap diambil" :value="$ringkas['approved']" tone="green" icon="check-circle" :href="route('admin.pengajuan.index', ['status' => 'approved'])" />
        <x-stat-card label="Selesai" :value="$ringkas['selesai']" tone="violet" icon="stamp" :href="route('admin.pengajuan.index', ['status' => 'selesai'])" />
        <x-stat-card label="Ditolak" :value="$ringkas['rejected']" tone="rose" icon="x-circle" :href="route('admin.pengajuan.index', ['status' => 'rejected'])" />
        <x-stat-card label="Siswa aktif" :value="$ringkas['siswa_aktif']" tone="coral" icon="users" :href="route('admin.siswa.index')" :hint="'dari ' . $ringkas['siswa'] . ' akun siswa'" />
        <x-stat-card label="Total arsip" :value="$ringkas['total_arsip']" tone="teal" icon="archive" :href="route('admin.arsip-siswa.index')" />
        <x-stat-card label="Dokumen digital tersedia" :value="$ringkas['digital_tersedia']" tone="sky" icon="file" :href="route('admin.arsip-siswa.index')" />
        <x-stat-card label="Dokumen fisik tersedia" :value="$ringkas['fisik_tersedia']" tone="green" icon="package" :href="route('admin.arsip-siswa.index')" />
        <x-stat-card label="Siswa tanpa arsip" :value="$ringkas['arsip_belum_lengkap']" tone="amber" icon="users" :href="route('admin.arsip-siswa.index')" />
    </section>

    {{-- Grafik --}}
    <section class="stagger grid gap-6 lg:grid-cols-3">
        <div class="card card-pad lg:col-span-2">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h2 class="card-title">Pengajuan 6 bulan terakhir</h2>
                    <p class="mt-0.5 text-xs text-muted">Jumlah pengajuan baru per bulan.</p>
                </div>
                <x-badge tone="teal" :dot="false">{{ collect($bulanan)->sum('total') }} pengajuan</x-badge>
            </div>
            @if ($adaBulanan)
                <x-chart.bars :data="$bulanan" title="Pengajuan per bulan" />
            @else
                <x-empty-state art="search" title="Belum ada data pengajuan" text="Grafik akan tampil otomatis ketika siswa mulai mengajukan layanan." class="!py-8" />
            @endif
        </div>

        <div class="card card-pad">
            <h2 class="card-title">Distribusi jenis layanan</h2>
            <p class="mb-4 mt-0.5 text-xs text-muted">Berdasarkan seluruh pengajuan.</p>
            @if ($ringkas['total'] > 0)
                <x-chart.donut :items="$itemJenis" title="Distribusi jenis layanan" />
            @else
                <x-empty-state art="inbox" title="Belum ada pengajuan" class="!py-8" />
            @endif
        </div>
    </section>

    {{-- Tabel terbaru + aktivitas --}}
    <section class="stagger grid gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <div class="card-head">
                <h2 class="card-title">Pengajuan terbaru</h2>
                <a href="{{ route('admin.pengajuan.index') }}" class="link text-xs">Lihat semua</a>
            </div>
            @if ($pengajuanTerbaru->isEmpty())
                <x-empty-state title="Belum ada pengajuan" text="Pengajuan dari siswa akan muncul di sini." />
            @else
                <div class="table-wrap">
                    <table class="tbl tbl-cards-mobile">
                        <caption class="sr-only">Daftar pengajuan terbaru</caption>
                        <thead><tr><th scope="col">Tiket</th><th scope="col">Siswa</th><th scope="col">Layanan</th><th scope="col">Status</th><th scope="col">Tanggal</th></tr></thead>
                        <tbody>
                            @foreach ($pengajuanTerbaru as $p)
                                <tr>
                                    <td><a href="{{ route('admin.pengajuan.show', $p) }}" class="link whitespace-nowrap font-mono text-xs">{{ $p->kode_tiket }}</a></td>
                                    <td class="font-semibold">{{ $p->user->name }}</td>
                                    <td class="text-ink-2">{{ $p->labelJenisLayanan() }}</td>
                                    <td><x-status-badge :status="$p->status" /></td>
                                    <td class="whitespace-nowrap text-muted">{{ $p->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Aktivitas terbaru</h2>
                <a href="{{ route('admin.aktivitas.index') }}" class="link text-xs">Semua log</a>
            </div>
            <div class="card-pad">
                @if ($recentActivities->isEmpty())
                    <x-empty-state art="inbox" title="Belum ada aktivitas" class="!py-6" />
                @else
                    <div class="timeline">
                        @foreach ($recentActivities as $log)
                            <div class="timeline-item tone-{{ $log->tone() }}">
                                <span class="timeline-dot"><x-icon name="check" size="h-3 w-3" stroke="3" /></span>
                                <div class="min-w-0">
                                    <p class="text-sm leading-snug text-ink-2">{{ $log->description }}</p>
                                    <p class="mt-0.5 text-xs text-muted">{{ $log->user->name ?? 'Sistem' }} · {{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Akses cepat + panduan --}}
    <section class="stagger grid gap-6 lg:grid-cols-3">
        <div class="card card-pad lg:col-span-2">
            <h2 class="card-title mb-4">Akses cepat</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Pengajuan masuk', 'admin.pengajuan.index', 'clipboard-check', 'violet'],
                    ['Data siswa', 'admin.siswa.index', 'users', 'coral'],
                    ['Jenis dokumen', 'admin.dokumen.index', 'folder', 'amber'],
                    ['Laporan', 'admin.laporan.index', 'chart', 'green'],
                ] as [$label, $rute, $ikon, $tone])
                    <a href="{{ route($rute) }}" class="tone-{{ $tone }} group flex flex-col items-center gap-2.5 rounded-2xl p-4 text-center text-sm font-bold transition hover:-translate-y-0.5"
                       style="background: rgb(var(--c-surface-2)); border: 1px solid rgb(var(--c-line))">
                        <span class="chip h-11 w-11 transition group-hover:scale-105"><x-icon :name="$ikon" size="h-5 w-5" /></span>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="card card-pad flex flex-col justify-between">
            <div>
                <span class="chip tone-violet mb-3 h-11 w-11"><x-icon name="video" size="h-5 w-5" /></span>
                <h2 class="card-title">Pusat Panduan</h2>
                <p class="mt-1 text-sm text-ink-2">
                    @if ($jumlahPanduan > 0)
                        {{ $jumlahPanduan }} materi video/audio tersedia untuk membantu siswa dan petugas.
                    @else
                        Belum ada materi. Tambahkan video atau audio panduan agar siswa lebih mudah memakai {{ config('eksidu.nama') }}.
                    @endif
                </p>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('panduan.index') }}" class="btn btn-secondary btn-sm">Lihat panduan</a>
                <a href="{{ route('admin.panduan.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="h-4 w-4" /> Tambah materi</a>
            </div>
        </div>
    </section>
</div>
@endsection
