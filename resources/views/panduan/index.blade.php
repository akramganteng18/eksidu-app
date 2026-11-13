@extends('layouts.app')

@section('title', 'Pusat Panduan')

@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp
<x-page-header eyebrow="Bantuan" title="Pusat panduan" subtitle="Video dan audio untuk membantu Anda memakai {{ config('eksidu.nama') }} dengan mudah.">
    @if ($admin)<a href="{{ route('admin.panduan.create') }}" class="btn btn-primary"><x-icon name="plus" size="h-4 w-4" /> Tambah materi</a>@endif
</x-page-header>

@if ($daftar->isEmpty())
    <div class="card">
        <x-empty-state art="media" title="Belum ada materi panduan"
                       :text="$admin ? 'Tambahkan video atau audio panduan pertama untuk pengguna.' : 'Materi panduan akan ditambahkan oleh admin. Silakan cek kembali nanti.'">
            @if ($admin)<a href="{{ route('admin.panduan.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="h-4 w-4" /> Tambah materi</a>@endif
        </x-empty-state>
    </div>
@else
    <div class="stagger grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($daftar as $m)
            <a href="{{ route('panduan.show', $m) }}" class="card group block overflow-hidden p-3 transition hover:-translate-y-1">
                <div class="media-thumb {{ $m->isVideo() ? 'tone-violet' : 'tone-coral' }}">
                    <span class="play"><x-icon :name="$m->isVideo() ? 'play' : 'audio'" size="h-6 w-6" /></span>
                    <span class="badge tone-slate absolute left-3 top-3" style="background: rgb(var(--c-surface) / .9)"><x-icon :name="$m->isVideo() ? 'video' : 'audio'" size="h-3 w-3" /> {{ \App\Models\PanduanMedia::JENIS[$m->jenis] }}</span>
                </div>
                <div class="p-3 pt-4">
                    <h2 class="line-clamp-2 text-base font-bold">{{ $m->judul }}</h2>
                    @if ($m->deskripsi)<p class="mt-1 line-clamp-2 text-sm text-ink-2">{{ $m->deskripsi }}</p>@endif
                    @if ($admin && (! $m->aktif || $m->untuk !== 'semua'))
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @unless ($m->aktif)<x-badge tone="slate">Nonaktif</x-badge>@endunless
                            @if ($m->untuk !== 'semua')<x-badge tone="amber">{{ \App\Models\PanduanMedia::UNTUK[$m->untuk] }}</x-badge>@endif
                        </div>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
