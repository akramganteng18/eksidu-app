@props(['name', 'label', 'value' => null, 'rows' => 4, 'hint' => null, 'required' => false, 'id' => null, 'scope' => null])
@php
    $id = $id ?? str_replace(['[', ']', '.'], '_', $name);
    $pakaiOld = $scope === null || old('_modal') === $scope;
    $galat = $pakaiOld ? $errors->first($name) : '';
    $nilai = $pakaiOld ? old($name, $value) : $value;
    $desc = $galat ? $id . '-error' : ($hint ? $id . '-hint' : null);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="ml-0.5 text-[rgb(var(--t-rose-ink))]" aria-hidden="true">*</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              @if ($galat) aria-invalid="true" @endif
              @if ($desc) aria-describedby="{{ $desc }}" @endif
              {{ $attributes->except('class')->merge(['class' => 'textarea']) }}>{{ $nilai }}</textarea>
    @if ($galat)
        <p id="{{ $id }}-error" class="field-error"><x-icon name="warning" size="mt-px h-3.5 w-3.5" />{{ $galat }}</p>
    @endif
    @if ($hint)<p id="{{ $id }}-hint" class="hint">{{ $hint }}</p>@endif
</div>
