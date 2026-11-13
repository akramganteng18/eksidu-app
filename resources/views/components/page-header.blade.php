@props(['title', 'subtitle' => null, 'eyebrow' => null])
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($eyebrow)<p class="mb-1 text-xs font-bold uppercase tracking-wider text-brand">{{ $eyebrow }}</p>@endif
        <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 max-w-2xl text-sm text-ink-2">{{ $subtitle }}</p>@endif
    </div>
    @if (! $slot->isEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
