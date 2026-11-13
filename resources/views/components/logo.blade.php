@props(["size" => 40, "wordmark" => true, "subtitle" => true, "hero" => false])
{{-- Logo EKSiDU: huruf "E" dari tiga lembar dokumen bertumpuk + titik "i". Warna mengikuti tema. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3' . ($hero ? ' logo-hero' : '')]) }}>
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" role="img" aria-label="Logo {{ config('eksidu.nama') }}" class="shrink-0">
        <rect class="logo-bg" width="48" height="48" rx="13"/>
        <rect class="logo-bar" x="14" y="12" width="6" height="24" rx="3"/>
        <rect class="logo-bar" x="14" y="12" width="19" height="6" rx="3"/>
        <rect class="logo-bar" x="14" y="21" width="14" height="6" rx="3"/>
        <rect class="logo-bar" x="14" y="30" width="19" height="6" rx="3"/>
        <circle class="logo-dot" cx="37" cy="15" r="3.4"/>
    </svg>
    @if ($wordmark)
        <span class="leading-tight">
            <span class="block font-display text-xl font-extrabold tracking-tight">EKS<span class="text-brand">i</span>DU</span>
            @if ($subtitle)
                <span class="block text-[11px] font-semibold text-muted">{{ config('eksidu.sekolah_singkat') }}</span>
            @endif
        </span>
    @endif
</span>
