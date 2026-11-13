@props(['status'])
@php
    $label = \App\Models\Pengajuan::STATUS_LABEL[$status] ?? ucfirst((string) $status);
    $tone = match ($status) {
        'pending' => 'amber', 'diproses' => 'sky', 'approved' => 'green',
        'selesai' => 'teal', 'rejected' => 'rose', default => 'slate',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge tone-{$tone}"]) }} data-status="{{ $status }}">{{ $label }}</span>
