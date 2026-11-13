@props(['name', 'label', 'options' => [], 'selected' => null, 'placeholder' => null, 'hint' => null, 'required' => false, 'id' => null])
@php
    $id = $id ?? str_replace(['[', ']', '.'], '_', $name);
    $galat = $errors->first($name);
    $pilih = (string) old($name, $selected);
    $desc = $galat ? $id . '-error' : ($hint ? $id . '-hint' : null);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="ml-0.5 text-[rgb(var(--t-rose-ink))]" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
            @if ($galat) aria-invalid="true" @endif
            @if ($desc) aria-describedby="{{ $desc }}" @endif
            {{ $attributes->except('class')->merge(['class' => 'select']) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $nilai => $teks)
            <option value="{{ $nilai }}" @selected((string) $nilai === $pilih)>{{ $teks }}</option>
        @endforeach
    </select>
    @if ($galat)
        <p id="{{ $id }}-error" class="field-error"><x-icon name="warning" size="mt-px h-3.5 w-3.5" />{{ $galat }}</p>
    @endif
    @if ($hint)<p id="{{ $id }}-hint" class="hint">{{ $hint }}</p>@endif
</div>
