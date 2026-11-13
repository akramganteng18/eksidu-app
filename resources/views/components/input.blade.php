@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'icon' => null, 'id' => null, 'scope' => null])
@php
    $id = $id ?? str_replace(['[', ']', '.'], '_', $name);
    // scope: dipakai pada form di dalam modal agar old()/error hanya tampil di modal yang benar.
    $pakaiOld = $scope === null || old('_modal') === $scope;
    $galat = $pakaiOld ? $errors->first($name) : '';
    $nilai = $pakaiOld ? old($name, $value) : $value;
    $desc = $galat ? $id . '-error' : ($hint ? $id . '-hint' : null);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="ml-0.5 text-[rgb(var(--t-rose-ink))]" aria-hidden="true">*</span>@endif</label>
    <div @class(['input-icon' => $icon])>
        @if ($icon)<x-icon :name="$icon" size="h-4 w-4" class="icon-left" />@endif
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               @if ($type !== 'password' && $type !== 'file') value="{{ $nilai }}" @endif
               @required($required)
               @if ($galat) aria-invalid="true" @endif
               @if ($desc) aria-describedby="{{ $desc }}" @endif
               {{ $attributes->except('class')->merge(['class' => 'input']) }}>
    </div>
    @if ($galat)
        <p id="{{ $id }}-error" class="field-error"><x-icon name="warning" size="mt-px h-3.5 w-3.5" />{{ $galat }}</p>
    @endif
    @if ($hint)<p id="{{ $id }}-hint" class="hint">{{ $hint }}</p>@endif
</div>
