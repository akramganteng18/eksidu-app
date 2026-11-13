@props(['title', 'text' => null, 'art' => 'docs'])
<div {{ $attributes->merge(['class' => 'empty']) }}>
    <x-illustration :name="$art" class="mb-5 h-32 w-auto" />
    <h3 class="text-base font-bold">{{ $title }}</h3>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-muted">{{ $text }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-5 flex flex-wrap items-center justify-center gap-2">{{ $slot }}</div>@endif
</div>
