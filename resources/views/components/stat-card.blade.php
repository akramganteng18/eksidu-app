@props(['label', 'value', 'tone' => 'teal', 'icon' => 'document', 'href' => null, 'lime' => false, 'hint' => null])
@php
    $tag = $href ? 'a' : 'div';
    $kelas = "stat tone-{$tone}" . ($lime ? ' stat-lime' : '');
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $kelas]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="relative z-10 min-w-0">
            <div class="stat-num" data-count="{{ (int) $value }}">{{ number_format((int) $value, 0, ',', '.') }}</div>
            <div class="stat-label">{{ $label }}</div>
            @if ($hint)<div class="mt-1 text-xs font-medium opacity-80">{{ $hint }}</div>@endif
        </div>
        <span class="stat-ico relative z-10"><x-icon :name="$icon" size="h-5 w-5" /></span>
    </div>
</{{ $tag }}>
