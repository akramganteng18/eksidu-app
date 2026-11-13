@props(['tone' => 'slate', 'dot' => true])
<span {{ $attributes->merge(['class' => "badge tone-{$tone}" . ($dot ? '' : ' badge-plain')]) }}>{{ $slot }}</span>
