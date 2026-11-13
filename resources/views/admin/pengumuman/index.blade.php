@extends('layouts.app')
@section('title', 'Pengumuman Sekolah')
@section('content')
<div class="space-y-6">
    <x-page-header eyebrow="Informasi sekolah" title="Pengumuman sekolah" subtitle="Kelola informasi resmi yang tampil pada dashboard sesuai sasaran pengguna.">
        <a class="btn btn-primary" href="{{ route('admin.pengumuman.create') }}"><x-icon name="plus" size="h-4 w-4"/> Buat pengumuman</a>
    </x-page-header>
    <div class="card overflow-hidden">
        @forelse($pengumumans as $item)
            <article class="card-pad space-y-3" @if(!$loop->first) style="border-top:1px solid rgb(var(--c-line))" @endif>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <x-badge :tone="$item->diterbitkan ? 'green' : 'slate'">{{ $item->diterbitkan ? 'Diterbitkan' : 'Draft' }}</x-badge>
                            <x-badge tone="sky">{{ ['semua'=>'Semua pengguna','siswa'=>'Siswa','admin'=>'Admin/TU'][$item->sasaran] ?? $item->sasaran }}</x-badge>
                        </div>
                        <h2 class="text-base font-extrabold">{{ $item->judul }}</h2>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink-2">{{ \Illuminate\Support\Str::limit($item->isi, 360) }}</p>
                        <p class="mt-2 text-xs text-muted">Dibuat {{ $item->created_at->format('d M Y H:i') }}@if($item->berakhir_pada) · Berakhir {{ $item->berakhir_pada->format('d M Y H:i') }}@endif</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.pengumuman.edit', $item) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.pengumuman.destroy', $item) }}" data-confirm="Hapus pengumuman ini?">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm" type="submit">Hapus</button></form>
                    </div>
                </div>
            </article>
        @empty
            <x-empty-state art="docs" title="Belum ada pengumuman" text="Buat pengumuman pertama untuk siswa atau petugas TU." />
        @endforelse
        @if($pengumumans->hasPages())<div class="card-pad">{{ $pengumumans->links() }}</div>@endif
    </div>
</div>
@endsection
