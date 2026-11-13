@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
@php $adaFilter = collect(request()->only(['q', 'user_id', 'action', 'dari', 'sampai']))->filter()->isNotEmpty(); @endphp
<x-page-header eyebrow="Laporan & sistem" title="Log aktivitas" subtitle="Jejak audit seluruh tindakan penting di dalam sistem." />

<form method="GET" action="{{ route('admin.aktivitas.index') }}" class="card card-pad mb-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2"><label for="q" class="label">Cari</label>
            <div class="input-icon"><x-icon name="search" size="h-4 w-4" class="icon-left" /><input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Deskripsi, aksi, atau nama" autocomplete="off"></div></div>
        <x-select name="user_id" label="Pengguna" placeholder="Semua pengguna" :options="$daftarUser->pluck('name', 'id')->all()" :selected="request('user_id')" />
        <x-select name="action" label="Aksi" placeholder="Semua aksi" :options="$daftarAksi->mapWithKeys(fn ($a) => [$a => ucfirst(str_replace('_', ' ', $a))])->all()" :selected="request('action')" />
        <div class="grid grid-cols-2 gap-2">
            <x-input name="dari" type="date" label="Dari" :value="request('dari')" />
            <x-input name="sampai" type="date" label="Sampai" :value="request('sampai')" />
        </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="search" size="h-4 w-4" /> Terapkan</button>
        @if ($adaFilter)<a href="{{ route('admin.aktivitas.index') }}" class="btn btn-secondary btn-sm">Reset</a>@endif
    </div>
</form>

<div class="card overflow-hidden">
    @if ($logs->isEmpty())
        <x-empty-state art="search" title="Tidak ada aktivitas" text="Belum ada log yang cocok dengan filter." />
    @else
        <div class="table-wrap">
            <table class="tbl">
                <caption class="sr-only">Log aktivitas</caption>
                <thead><tr><th scope="col">Waktu</th><th scope="col">Pengguna</th><th scope="col">Aksi</th><th scope="col">Keterangan</th></tr></thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-muted">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="font-semibold">{{ $log->user->name ?? 'Sistem' }}</td>
                            <td><x-badge :tone="$log->tone()">{{ $log->labelAksi() }}</x-badge></td>
                            <td class="text-ink-2">{{ $log->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-pad pt-2">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
