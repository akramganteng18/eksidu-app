@props(['items', 'title' => 'Distribusi'])
{{-- Donat SVG. $items = [['label'=>'Legalisir','total'=>3], ...] (maks 4 warna seg-1..seg-4) --}}
@php
    $jumlah = max(0, (int) collect($items)->sum('total'));
    $r = 15.9155;                 // keliling = 100 sehingga stroke-dasharray = persen
    $offset = 25;                 // mulai dari jam 12
    $ringkas = collect($items)->map(fn ($d) => "{$d['label']}: {$d['total']}")->implode('; ');
@endphp
<div class="flex flex-col items-center gap-5 sm:flex-row sm:items-center">
    <div class="relative h-40 w-40 shrink-0">
        <svg viewBox="0 0 36 36" class="h-full w-full -rotate-0" role="img" aria-label="{{ $title }}. {{ $ringkas }}">
            <circle cx="18" cy="18" r="{{ $r }}" fill="none" stroke-width="4.2" style="stroke: rgb(var(--c-surface-3))"/>
            @if ($jumlah > 0)
                @foreach ($items as $i => $d)
                    @php
                        $persen = $d['total'] / $jumlah * 100;
                        $dash = max(0, $persen - ($persen > 0 && count($items) > 1 ? 1.2 : 0));
                    @endphp
                    @if ($d['total'] > 0)
                        <circle class="seg seg-{{ ($i % 4) + 1 }}" cx="18" cy="18" r="{{ $r }}" fill="none" stroke-width="4.2"
                                stroke-linecap="butt" stroke-dasharray="{{ round($dash, 2) }} {{ round(100 - $dash, 2) }}"
                                stroke-dashoffset="{{ round($offset, 2) }}"><title>{{ $d['label'] }}: {{ $d['total'] }}</title></circle>
                        @php $offset -= $persen; @endphp
                    @endif
                @endforeach
            @endif
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="font-display text-3xl font-extrabold leading-none" data-count="{{ $jumlah }}">{{ $jumlah }}</span>
            <span class="mt-1 text-[11px] font-bold uppercase tracking-wide text-muted">Total</span>
        </div>
    </div>
    <ul class="w-full min-w-0 flex-1 space-y-2.5 text-sm">
        @foreach ($items as $i => $d)
            <li class="flex items-center justify-between gap-3">
                <span class="flex min-w-0 items-center gap-2"><span class="legend-dot seg-{{ ($i % 4) + 1 }}" aria-hidden="true"></span><span class="truncate text-ink-2">{{ $d['label'] }}</span></span>
                <span class="num font-bold tabular-nums">{{ $d['total'] }}
                    <span class="ml-1 text-xs font-semibold text-muted">{{ $jumlah > 0 ? round($d['total'] / $jumlah * 100) : 0 }}%</span></span>
            </li>
        @endforeach
    </ul>
</div>
