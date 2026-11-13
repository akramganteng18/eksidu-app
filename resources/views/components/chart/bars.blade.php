@props(['data', 'title' => 'Grafik batang'])
{{-- Grafik batang SVG dari data nyata. $data = [['label'=>'Okt','bulan'=>'Oktober 2026','total'=>5], ...] --}}
@php
    $maks = max(1, collect($data)->max('total'));
    $skala = $maks <= 4 ? 4 : (int) (ceil($maks / 4) * 4);   // sumbu Y kelipatan 4
    $n = count($data);
    $lebar = 420; $tinggi = 200; $kiri = 30; $bawah = 28; $atas = 18;
    $area = $tinggi - $bawah - $atas;
    $slot = ($lebar - $kiri) / max(1, $n);
    $bar = min(38, $slot * 0.56);
    $ringkas = collect($data)->map(fn ($d) => "{$d['bulan']}: {$d['total']}")->implode('; ');
@endphp
<svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" class="h-auto w-full" role="img" aria-label="{{ $title }}. {{ $ringkas }}">
    @for ($i = 0; $i <= 4; $i++)
        @php $y = $atas + $area - ($area * $i / 4); @endphp
        <line x1="{{ $kiri }}" x2="{{ $lebar }}" y1="{{ $y }}" y2="{{ $y }}" class="chart-grid" stroke-width="1"/>
        <text x="{{ $kiri - 8 }}" y="{{ $y + 4 }}" text-anchor="end" class="chart-text">{{ (int) round($skala * $i / 4) }}</text>
    @endfor
    @foreach ($data as $i => $d)
        @php
            $h = $d['total'] > 0 ? max(4, $area * $d['total'] / $skala) : 0;
            $x = $kiri + $slot * $i + ($slot - $bar) / 2;
            $y = $atas + $area - $h;
        @endphp
        <g class="chart-bar {{ $loop->last ? 'is-last' : '' }}" style="animation-delay: {{ $i * 70 }}ms">
            <title>{{ $d['bulan'] }}: {{ $d['total'] }} pengajuan</title>
            @if ($h > 0)<rect x="{{ $x }}" y="{{ $y }}" width="{{ $bar }}" height="{{ $h }}" rx="7"/>@endif
        </g>
        @if ($d['total'] > 0)
            <text x="{{ $x + $bar / 2 }}" y="{{ $y - 6 }}" text-anchor="middle" class="chart-value">{{ $d['total'] }}</text>
        @endif
        <text x="{{ $x + $bar / 2 }}" y="{{ $tinggi - 8 }}" text-anchor="middle" class="chart-text">{{ $d['label'] }}</text>
    @endforeach
</svg>
