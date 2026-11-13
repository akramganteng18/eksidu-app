@extends('layouts.app')

@section('title', 'Lacak Tiket')

@section('content')
<x-page-header eyebrow="Layanan saya" title="Lacak tiket" subtitle="Masukkan kode tiket untuk melihat status dan riwayat pengajuan Anda." />

<div class="grid gap-6 lg:grid-cols-5">
    <form method="POST" action="{{ route('siswa.pengajuan.lacak.cari') }}" class="card card-pad space-y-5 lg:col-span-3" novalidate>
        @csrf
        <x-input name="kode_tiket" label="Kode tiket" icon="search" required autofocus autocomplete="off" class="font-mono"
                 placeholder="{{ config('eksidu.prefix_tiket') }}-{{ date('Y') }}-000001"
                 hint="Kode tiket tertera di halaman detail pengajuan dan notifikasi Anda. Tiket lama berawalan SIMADA tetap dapat dilacak." />
        <button type="submit" class="btn btn-primary"><x-icon name="search" size="h-[18px] w-[18px]" /> Cari tiket</button>
    </form>

    <div class="card card-pad lg:col-span-2">
        <x-illustration name="search" class="mx-auto mb-4 h-28 w-auto" />
        <p class="text-center text-sm text-ink-2">Pencarian hanya menampilkan pengajuan milik akun Anda sendiri.</p>
    </div>
</div>
@endsection
