@props(['user', 'size' => 'h-9 w-9 text-sm'])
@php $foto = $user->fotoUrl(); @endphp
<span {{ $attributes->merge(['class' => 'avatar ' . $size]) }}>
    @if ($foto)
        <img src="{{ $foto }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover" loading="lazy"
             onerror="this.remove()">
    @endif
    @unless ($foto)<span aria-hidden="true">{{ $user->initials() }}</span>@endunless
</span>
