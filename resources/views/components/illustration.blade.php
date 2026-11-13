@props(['name' => 'docs'])
@switch($name)
    @case('hero')
        {{-- Hero: tumpukan dokumen + toga + lencana centang --}}
        <svg {{ $attributes }} viewBox="0 0 300 220" fill="none" role="img" aria-label="Ilustrasi dokumen akademik dan toga">
            <rect class="ha-doc-back" x="70" y="38" width="120" height="150" rx="14" transform="rotate(-9 130 113)"/>
            <rect class="ha-doc-back" x="96" y="30" width="120" height="150" rx="14" transform="rotate(6 156 105)"/>
            <rect class="ha-doc" x="86" y="44" width="124" height="156" rx="14"/>
            <rect class="ha-bar-strong" x="104" y="66" width="54" height="9" rx="4.5"/>
            <rect class="ha-bar" x="104" y="88" width="88" height="7" rx="3.5"/>
            <rect class="ha-bar" x="104" y="104" width="76" height="7" rx="3.5"/>
            <rect class="ha-bar" x="104" y="120" width="88" height="7" rx="3.5"/>
            <rect class="ha-bar" x="104" y="136" width="52" height="7" rx="3.5"/>
            <circle class="ha-ok" cx="190" cy="176" r="24"/>
            <path d="M178 176l8 8 15-17" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" class="only-light"/>
            <path d="M178 176l8 8 15-17" stroke="#0e1013" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" class="only-dark"/>
            <path class="ha-cap" d="M222 58l46 17-46 17-46-17z"/>
            <path class="ha-cap-top" d="M200 86v18c0 6 11 11 22 11s22-5 22-11V86l-22 8z"/>
            <path d="M268 75v26" stroke="rgb(var(--t-amber))" stroke-width="3.5" stroke-linecap="round"/>
            <circle class="ha-accent" cx="268" cy="106" r="5"/>
            <circle class="ha-accent" cx="52" cy="70" r="5" opacity=".9"/>
            <circle cx="40" cy="150" r="9" fill="rgb(255 255 255 / .22)"/>
            <path d="M262 150l6 6m0-6l-6 6" stroke="rgb(255 255 255 / .55)" stroke-width="3" stroke-linecap="round"/>
        </svg>
        @break
    @case('search')
        <svg {{ $attributes }} viewBox="0 0 200 140" fill="none" role="img" aria-label="Ilustrasi pencarian tanpa hasil">
            <circle class="il-brand-soft" cx="100" cy="70" r="56"/>
            <rect class="il-surface il-line" x="52" y="36" width="64" height="78" rx="8"/>
            <rect class="il-soft" x="62" y="50" width="30" height="5" rx="2.5"/>
            <rect class="il-soft" x="62" y="62" width="42" height="4" rx="2"/>
            <rect class="il-soft" x="62" y="72" width="36" height="4" rx="2"/>
            <circle class="il-surface" cx="124" cy="80" r="24" stroke="rgb(var(--c-brand))" stroke-width="6"/>
            <path d="M142 98l20 20" stroke="rgb(var(--c-brand))" stroke-width="7" stroke-linecap="round"/>
            <path d="M116 80h16" stroke="rgb(var(--c-muted))" stroke-width="4" stroke-linecap="round"/>
            <circle class="il-warm" cx="48" cy="40" r="4"/><circle class="il-accent" cx="160" cy="44" r="3.5"/>
        </svg>
        @break
    @case('inbox')
        <svg {{ $attributes }} viewBox="0 0 200 140" fill="none" role="img" aria-label="Ilustrasi kotak masuk kosong">
            <circle class="il-brand-soft" cx="100" cy="72" r="56"/>
            <path class="il-surface il-line" d="M52 78l14-34a8 8 0 017.4-5h53.2a8 8 0 017.4 5l14 34v28a8 8 0 01-8 8H60a8 8 0 01-8-8z"/>
            <path class="il-soft il-line" d="M52 78h32a8 8 0 018 8 8 8 0 008 8h0a8 8 0 008-8 8 8 0 018-8h32"/>
            <path class="il-brand" d="M100 24l4.5 9.5L115 35l-7.5 7.2L109.300 52.500 100 47.500l-9.300 5 1.800-10.300L85 35l10.500-1.500z" opacity=".95"/>
            <circle class="il-coral" cx="50" cy="38" r="4"/><circle class="il-warm" cx="156" cy="52" r="3.5"/>
        </svg>
        @break
    @case('media')
        <svg {{ $attributes }} viewBox="0 0 200 140" fill="none" role="img" aria-label="Ilustrasi materi video dan audio">
            <circle class="il-brand-soft" cx="100" cy="70" r="56"/>
            <rect class="il-surface il-line" x="46" y="36" width="108" height="68" rx="10"/>
            <rect class="il-soft" x="46" y="36" width="108" height="14" rx="10"/>
            <circle class="il-coral" cx="58" cy="43" r="2.6"/><circle class="il-warm" cx="67" cy="43" r="2.6"/><circle class="il-brand" cx="76" cy="43" r="2.6"/>
            <circle class="il-brand" cx="100" cy="78" r="18"/>
            <path d="M95 69l16 9-16 9z" fill="rgb(var(--c-brand-fg))"/>
            <path d="M52 118h96" stroke="rgb(var(--c-line-strong))" stroke-width="4" stroke-linecap="round"/>
            <path d="M52 118h40" stroke="rgb(var(--c-brand))" stroke-width="4" stroke-linecap="round"/>
        </svg>
        @break
    @case('success')
        <svg {{ $attributes }} viewBox="0 0 200 140" fill="none" role="img" aria-label="Ilustrasi berhasil">
            <circle class="il-brand-soft" cx="100" cy="70" r="56"/>
            <circle class="il-brand" cx="100" cy="70" r="34"/>
            <path d="M84 71l11 11 22-24" class="il-brand-fg" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            <circle class="il-warm" cx="48" cy="40" r="4"/><circle class="il-accent" cx="154" cy="40" r="4"/><circle class="il-coral" cx="150" cy="108" r="3.5"/>
        </svg>
        @break
    @case('lock')
        <svg {{ $attributes }} viewBox="0 0 200 140" fill="none" role="img" aria-label="Ilustrasi akses dibatasi">
            <circle class="il-brand-soft" cx="100" cy="70" r="56"/>
            <path d="M76 62V52a24 24 0 0148 0v10" stroke="rgb(var(--c-ink-2))" stroke-width="8" stroke-linecap="round"/>
            <rect class="il-brand" x="64" y="60" width="72" height="54" rx="12"/>
            <circle cx="100" cy="82" r="7" fill="rgb(var(--c-brand-fg))"/>
            <rect x="97" y="86" width="6" height="14" rx="3" fill="rgb(var(--c-brand-fg))"/>
            <circle class="il-warm" cx="48" cy="44" r="4"/><circle class="il-accent" cx="156" cy="52" r="3.5"/>
        </svg>
        @break
    @default
        <svg {{ $attributes }} viewBox="0 0 200 140" fill="none" role="img" aria-label="Ilustrasi dokumen kosong">
            <circle class="il-brand-soft" cx="100" cy="72" r="56"/>
            <rect class="il-soft il-line" x="62" y="30" width="62" height="80" rx="8" transform="rotate(-8 93 70)"/>
            <rect class="il-surface il-line" x="78" y="36" width="62" height="82" rx="8"/>
            <rect class="il-brand" x="90" y="52" width="32" height="6" rx="3"/>
            <rect class="il-soft" x="90" y="66" width="40" height="4.500" rx="2.250"/>
            <rect class="il-soft" x="90" y="77" width="40" height="4.500" rx="2.250"/>
            <rect class="il-soft" x="90" y="88" width="24" height="4.500" rx="2.250"/>
            <circle class="il-brand" cx="140" cy="108" r="14"/>
            <path d="M133 108l5 5 9-10" class="il-brand-fg" stroke-width="3.500" stroke-linecap="round" stroke-linejoin="round"/>
            <circle class="il-warm" cx="50" cy="44" r="4"/><circle class="il-accent" cx="158" cy="46" r="3.500"/>
        </svg>
@endswitch
