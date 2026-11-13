@props(['panduan'])
{{-- Pemutar materi panduan. Selalu menyediakan fallback bila media gagal dimuat; tidak pernah autoplay. --}}
@if ($panduan->isTautan())
    @php $embed = $panduan->urlEmbed(); @endphp
    @if ($embed)
        <div class="media-frame" x-data="{ siap: false }">
            <div x-show="!siap" class="skeleton absolute inset-0 !rounded-none" aria-hidden="true"></div>
            <iframe src="{{ $embed }}" title="{{ $panduan->judul }}" loading="lazy" allowfullscreen
                    allow="encrypted-media; picture-in-picture; fullscreen"
                    referrerpolicy="strict-origin-when-cross-origin"
                    sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"
                    @load="siap = true"></iframe>
        </div>
        <p class="mt-2 text-xs text-muted">Diputar melalui YouTube (mode privasi ditingkatkan). Bila tidak tampil,
            <a href="{{ $panduan->url }}" target="_blank" rel="noopener noreferrer" class="link">buka di YouTube</a>.</p>
    @else
        <div class="alert tone-amber"><x-icon name="warning" /> Tautan video tidak valid. Hubungi admin untuk memperbaikinya.</div>
    @endif
@elseif ($panduan->isVideo())
    <div class="media-frame" x-data="{ gagal: false, memuat: true }" x-on:error.capture="gagal = true; memuat = false">
        <div x-show="memuat && !gagal" class="skeleton absolute inset-0 !rounded-none" aria-hidden="true"></div>
        <video controls preload="metadata" playsinline controlslist="nodownload" x-show="!gagal"
               src="{{ route('panduan.stream', $panduan) }}" @loadedmetadata="memuat = false"
               aria-label="Video: {{ $panduan->judul }}">
            Peramban Anda tidak mendukung pemutar video.
        </video>
        <div x-show="gagal" x-cloak class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
            <x-icon name="warning" size="h-8 w-8" />
            <p class="text-sm font-semibold">Video tidak dapat dimuat.</p>
            <p class="max-w-xs text-xs text-muted">Berkas mungkin dihapus atau format tidak didukung peramban Anda.</p>
            <button type="button" class="btn btn-secondary btn-sm" @click="gagal = false; memuat = true; $el.closest('.media-frame').querySelector('video').load()">Coba lagi</button>
        </div>
    </div>
@else
    <div class="card card-pad" x-data="{ gagal: false }" x-on:error.capture="gagal = true">
        <div class="mb-4 flex items-center gap-3">
            <span class="chip tone-violet h-12 w-12"><x-icon name="audio" size="h-6 w-6" /></span>
            <div class="min-w-0"><p class="truncate text-sm font-bold">{{ $panduan->judul }}</p><p class="text-xs text-muted">Panduan audio</p></div>
        </div>
        <audio controls preload="none" x-show="!gagal" src="{{ route('panduan.stream', $panduan) }}"
               aria-label="Audio: {{ $panduan->judul }}">Peramban Anda tidak mendukung pemutar audio.</audio>
        <div x-show="gagal" x-cloak class="alert tone-amber"><x-icon name="warning" /> Audio tidak dapat dimuat. Coba muat ulang halaman.</div>
    </div>
@endif
