@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    $jam = now()->hour;
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    $namaDepan = explode(' ', trim($user->name))[0];
    $dalamProses = $ringkasan['pending'] + $ringkasan['diproses'];
@endphp

<div class="space-y-6">
    {{-- Hero personal --}}
    <section class="stagger grid gap-6">
        <div class="hero p-6 sm:p-8">
            <div class="grid items-center gap-6 md:grid-cols-[1fr_auto]">
                <div>
                    <p class="hero-muted text-sm font-semibold">{{ $user->kelas ?: 'Siswa' }}@if ($user->nis_nip) · NIS {{ $user->nis_nip }}@endif</p>
                    <h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ $salam }}, {{ $namaDepan }}!</h1>
                    <p class="hero-muted mt-2 max-w-lg text-sm leading-relaxed">
                        Ajukan layanan dokumen akademik dari mana saja, lalu pantau perkembangannya langsung dari sini.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2.5">
                        <a href="{{ route('siswa.pengajuan.create') }}" class="btn btn-hero"><x-icon name="plus" size="h-[18px] w-[18px]" /> Ajukan layanan baru</a>
                        <a href="{{ route('siswa.pengajuan.lacak.form') }}" class="btn btn-hero-outline"><x-icon name="search" size="h-[18px] w-[18px]" /> Lacak tiket</a>
                    </div>
                </div>
                <x-illustration name="hero" class="hidden h-40 w-auto md:block lg:h-48" />
            </div>
        </div>

        @if ($siapDiambil)
            <div class="alert tone-green items-center" role="status">
                <x-icon name="check-circle" size="h-6 w-6" />
                <p class="flex-1"><strong>{{ $siapDiambil->bentuk_dokumen === 'digital' ? 'Dokumen digital Anda siap diakses!' : 'Dokumen fisik Anda siap diambil!' }}</strong> Tiket {{ $siapDiambil->kode_tiket }} ({{ $siapDiambil->labelJenisLayanan() }}). {{ $siapDiambil->bentuk_dokumen === 'digital' ? 'Buka preview atau unduh dari detail pengajuan.' : 'Tunjukkan QR atau kode verifikasi kepada petugas TU.' }}</p>
                <a href="{{ route('siswa.pengajuan.show', $siapDiambil) }}" class="btn btn-success btn-sm">Tampilkan QR</a>
            </div>
        @endif
    </section>

    @if($pengumuman->isNotEmpty())
        <section class="card card-pad space-y-3" aria-labelledby="judul-pengumuman-siswa">
            <div class="flex items-center justify-between gap-3"><h2 id="judul-pengumuman-siswa" class="card-title flex items-center gap-2"><x-icon name="bell" size="h-4 w-4"/> Pengumuman sekolah</h2><span class="text-xs text-muted">{{ $pengumuman->count() }} informasi</span></div>
            @foreach($pengumuman as $item)
                <article class="rounded-xl p-4" style="background:rgb(var(--c-surface-2))">
                    <h3 class="text-sm font-extrabold">{{ $item->judul }}</h3>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink-2">{{ $item->isi }}</p>
                </article>
            @endforeach
        </section>
    @endif

    {{-- Statistik --}}
    <section class="stagger grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Ringkasan pengajuan saya">
        <x-stat-card label="Total pengajuan" :value="$ringkasan['total']" tone="teal" icon="document" lime :href="route('siswa.pengajuan.index')" />
        <x-stat-card label="Dalam proses" :value="$dalamProses" tone="amber" icon="clock" :href="route('siswa.pengajuan.index')" hint="Menunggu & diproses" />
        <x-stat-card label="Siap diambil" :value="$ringkasan['siap']" tone="green" icon="check-circle" :href="route('siswa.pengajuan.index', ['status' => 'approved'])" />
        <x-stat-card label="Selesai" :value="$ringkasan['selesai']" tone="violet" icon="stamp" :href="route('siswa.pengajuan.index', ['status' => 'selesai'])" />
        <x-stat-card label="Dokumen digital aktif" :value="$dokumenDigitalAktif" tone="sky" icon="file" :href="route('siswa.dokumen-saya.index')" />
    </section>

    {{-- Layanan cepat --}}
    <section class="stagger grid gap-4 md:grid-cols-2 xl:grid-cols-5" aria-label="Pilihan layanan">
        @foreach ([
            ['Rapor', 'Pilih tahun ajaran, semester, dan periode rapor.', 'book', 'violet'],
            ['TKA', 'Permintaan dokumen TKA.', 'file-text', 'sky'],
            ['SKLS', 'Permintaan Surat Keterangan Lulus.', 'file-check', 'teal'],
            ['Ijazah', 'Permintaan dokumen ijazah.', 'graduation-cap', 'coral'],
            ['Legalisir', 'Ajukan legalisir dokumen akademik.', 'stamp', 'amber'],
        ] as [$judul, $teks, $ikon, $tone])
            <a href="{{ route('siswa.pengajuan.create') }}" class="card tone-{{ $tone }} group block p-5 transition hover:-translate-y-1">
                <span class="chip h-11 w-11"><x-icon :name="$ikon" size="h-5 w-5" /></span>
                <h3 class="mt-3 text-base font-bold">{{ $judul }}</h3>
                <p class="mt-1 text-sm text-ink-2">{{ $teks }}</p>
                <p class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold" style="color: rgb(var(--tone-ink))">Ajukan <x-icon name="arrow-right" size="h-4 w-4" /></p>
            </a>
        @endforeach
    </section>

    {{-- Riwayat + alur --}}
    <section class="stagger grid gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <div class="card-head">
                <h2 class="card-title">Pengajuan terakhir saya</h2>
                <a href="{{ route('siswa.pengajuan.index') }}" class="link text-xs">Lihat semua</a>
            </div>
            @if ($terbaru->isEmpty())
                <x-empty-state title="Belum ada pengajuan" text="Mulai ajukan layanan pertama Anda. Prosesnya hanya beberapa menit.">
                    <a href="{{ route('siswa.pengajuan.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="h-4 w-4" /> Ajukan layanan</a>
                </x-empty-state>
            @else
                <div class="table-wrap">
                    <table class="tbl tbl-cards-mobile">
                        <caption class="sr-only">Pengajuan terakhir</caption>
                        <thead><tr><th scope="col">Tiket</th><th scope="col">Layanan</th><th scope="col">Status</th><th scope="col">Tanggal</th></tr></thead>
                        <tbody>
                            @foreach ($terbaru as $p)
                                <tr>
                                    <td><a href="{{ route('siswa.pengajuan.show', $p) }}" class="link whitespace-nowrap font-mono text-xs">{{ $p->kode_tiket }}</a></td>
                                    <td class="font-semibold">{{ $p->labelJenisLayanan() }}</td>
                                    <td><x-status-badge :status="$p->status" /></td>
                                    <td class="whitespace-nowrap text-muted">{{ $p->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card card-pad">
            <h2 class="card-title mb-4 flex items-center gap-2"><x-icon name="lightbulb" size="h-4 w-4" /> Alur layanan</h2>
            <ol class="space-y-4">
                @foreach ([
                    ['Ajukan', 'Pilih layanan dan isi keperluan Anda.', 'violet'],
                    ['Diverifikasi', 'Petugas TU memeriksa dan memproses pengajuan.', 'sky'],
                    ['Siap diambil', 'Anda mendapat notifikasi beserta QR Code.', 'green'],
                    ['Selesai', 'Dokumen fisik diserahkan atau dokumen digital diakses sesuai persetujuan.', 'teal'],
                ] as $i => [$judul, $teks, $tone])
                    <li class="tone-{{ $tone }} flex gap-3">
                        <span class="chip h-8 w-8 shrink-0 text-sm font-extrabold">{{ $i + 1 }}</span>
                        <div><p class="text-sm font-bold">{{ $judul }}</p><p class="text-xs text-muted">{{ $teks }}</p></div>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('panduan.index') }}" class="btn btn-secondary btn-sm btn-block mt-5">
                <x-icon name="video" size="h-4 w-4" /> Pusat panduan @if ($jumlahPanduan > 0)({{ $jumlahPanduan }})@endif
            </a>
        </div>
    </section>

    @if ($aktivitasSaya->isNotEmpty())
        <section class="stagger card card-pad">
            <h2 class="card-title mb-4">Aktivitas saya</h2>
            <ul class="space-y-3 text-sm">
                @foreach ($aktivitasSaya as $log)
                    <li class="flex items-start justify-between gap-4">
                        <span class="text-ink-2">{{ $log->description }}</span>
                        <span class="shrink-0 text-xs text-muted">{{ $log->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
