@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        @if (method_exists($paginator, 'total'))
            <p class="text-xs font-medium text-muted" aria-live="polite">
                Menampilkan
                <span class="font-bold text-ink-2">{{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}</span>
                dari
                <span class="font-bold text-ink-2">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
                data
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-1" aria-label="Halaman">
            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm pointer-events-none opacity-40" aria-disabled="true">
                    <x-icon name="chevron-left" size="h-4 w-4" />
                    <span class="hidden sm:inline">Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm">
                    <x-icon name="chevron-left" size="h-4 w-4" />
                    <span class="hidden sm:inline">Sebelumnya</span>
                </a>
            @endif

            {{-- Nomor halaman dan elipsis dari Laravel --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 py-1.5 text-sm font-semibold text-muted" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-primary btn-sm min-w-9 justify-center" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-secondary btn-sm min-w-9 justify-center" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">
                    <span class="hidden sm:inline">Berikutnya</span>
                    <x-icon name="chevron-right" size="h-4 w-4" />
                </a>
            @else
                <span class="btn btn-secondary btn-sm pointer-events-none opacity-40" aria-disabled="true">
                    <span class="hidden sm:inline">Berikutnya</span>
                    <x-icon name="chevron-right" size="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
