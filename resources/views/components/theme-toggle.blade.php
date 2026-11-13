{{-- Tombol pengganti tema. Pilihan disimpan di localStorage (lihat resources/js/theme.js). --}}
<button type="button" x-data="themeToggle" @click="toggle()"
        {{ $attributes->merge(['class' => 'btn btn-ghost btn-icon']) }}
        :aria-label="theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
        :title="theme === 'dark' ? 'Mode terang' : 'Mode gelap'">
    <span x-show="theme !== 'dark'"><x-icon name="moon" /></span>
    <span x-show="theme === 'dark'" x-cloak><x-icon name="sun" /></span>
</button>
