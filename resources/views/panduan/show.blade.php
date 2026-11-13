@extends('layouts.app')

@section('title', $panduan->judul)

@section('content')
<x-page-header eyebrow="Pusat panduan" :title="$panduan->judul">
    @if (auth()->user()->isAdmin())<a href="{{ route('admin.panduan.edit', $panduan) }}" class="btn btn-secondary"><x-icon name="pencil" size="h-4 w-4" /> Ubah</a>@endif
    <a href="{{ route('panduan.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="h-4 w-4" /> Semua panduan</a>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <x-media.player :panduan="$panduan" />

        @if ($panduan->deskripsi)
            <div class="card card-pad">
                <h2 class="card-title mb-2">Tentang materi ini</h2>
                <p class="text-sm leading-relaxed text-ink-2">{{ $panduan->deskripsi }}</p>
            </div>
        @endif
    </div>

    <aside class="card card-pad h-fit">
        <h2 class="card-title mb-4">Materi lainnya</h2>
        @forelse ($lainnya as $m)
            <a href="{{ route('panduan.show', $m) }}" class="flex items-center gap-3 rounded-xl p-2 hover:bg-surface-2">
                <span class="chip {{ $m->isVideo() ? 'tone-violet' : 'tone-coral' }} h-11 w-11 shrink-0"><x-icon :name="$m->isVideo() ? 'video' : 'audio'" size="h-5 w-5" /></span>
                <span class="min-w-0"><span class="line-clamp-2 block text-sm font-bold">{{ $m->judul }}</span><span class="text-xs text-muted">{{ \App\Models\PanduanMedia::JENIS[$m->jenis] }}</span></span>
            </a>
        @empty
            <p class="text-sm text-muted">Belum ada materi lain.</p>
        @endforelse
    </aside>
</div>
@endsection
