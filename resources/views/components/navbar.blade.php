@php
    $user = auth()->user();
    $jumlahBelumDibaca = $user->unreadNotifications()->count();
    $notifTerbaru = $user->unreadNotifications()->latest()->limit(5)->get();
@endphp
<header class="topbar">
    <div class="flex min-w-0 items-center gap-2">
        <button type="button" class="btn btn-ghost btn-icon lg:hidden" @click="sidebarOpen = true"
                aria-label="Buka menu" aria-controls="sidebar" :aria-expanded="sidebarOpen">
            <x-icon name="menu" />
        </button>
        <div class="min-w-0 leading-tight">
            <p class="truncate text-sm font-bold">@yield('title', 'Dashboard')</p>
            <p class="hidden truncate text-xs text-muted sm:block">{{ config('eksidu.nama_panjang') }}</p>
        </div>
    </div>

    <div class="flex items-center gap-1 sm:gap-2">
        <x-theme-toggle />

        {{-- Notifikasi --}}
        <div class="relative" x-data="{ buka: false }" @keydown.escape="buka = false" @click.outside="buka = false">
            <button type="button" class="btn btn-ghost btn-icon relative" @click="buka = !buka"
                    aria-haspopup="true" :aria-expanded="buka"
                    aria-label="Notifikasi{{ $jumlahBelumDibaca ? ', ' . $jumlahBelumDibaca . ' belum dibaca' : '' }}">
                <x-icon name="bell" />
                @if ($jumlahBelumDibaca > 0)
                    <span class="absolute right-1 top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full px-1 text-[10px] font-extrabold"
                          style="background: rgb(var(--t-rose)); color: #fff">{{ $jumlahBelumDibaca > 9 ? '9+' : $jumlahBelumDibaca }}</span>
                @endif
            </button>
            <div x-show="buka" x-cloak x-transition.origin.top.right class="menu-pop w-[min(22rem,calc(100vw-2rem))]">
                <div class="flex items-center justify-between px-4 py-3" style="border-bottom: 1px solid rgb(var(--c-line))">
                    <p class="text-sm font-bold">Notifikasi</p>
                    @if ($jumlahBelumDibaca > 0)
                        <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
                            <button class="link text-xs">Tandai semua dibaca</button>
                        </form>
                    @endif
                </div>
                <div class="max-h-80 overflow-y-auto divide-line">
                    @forelse ($notifTerbaru as $n)
                        <a href="{{ route('notifications.open', $n->id) }}" class="block px-4 py-3 hover:bg-surface-2">
                            <p class="text-xs font-bold text-brand">{{ $n->data['kode_tiket'] ?? 'Pemberitahuan' }}</p>
                            <p class="mt-0.5 line-clamp-2 text-sm text-ink-2">{{ $n->data['message'] ?? '' }}</p>
                            <p class="mt-1 text-[11px] text-muted">{{ $n->created_at->diffForHumans() }}</p>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-muted">Tidak ada notifikasi baru.</div>
                    @endforelse
                </div>
                <a href="{{ route('notifications.index') }}" class="block px-4 py-2.5 text-center text-sm font-semibold text-brand hover:bg-surface-2"
                   style="border-top: 1px solid rgb(var(--c-line))">Lihat semua notifikasi</a>
            </div>
        </div>

        {{-- Menu akun --}}
        <div class="relative" x-data="{ buka: false }" @keydown.escape="buka = false" @click.outside="buka = false">
            <button type="button" class="flex items-center gap-2 rounded-xl p-1 pr-2 hover:bg-surface-2" @click="buka = !buka"
                    aria-haspopup="true" :aria-expanded="buka" aria-label="Menu akun {{ $user->name }}">
                <x-avatar :user="$user" />
                <span class="hidden text-left leading-tight md:block">
                    <span class="block max-w-[9rem] truncate text-sm font-bold">{{ $user->name }}</span>
                    <span class="block text-[11px] font-semibold text-muted">{{ $user->isAdmin() ? 'Admin / TU' : 'Siswa' }}</span>
                </span>
                <x-icon name="chevron-down" size="hidden h-4 w-4 text-muted md:block" />
            </button>
            <div x-show="buka" x-cloak x-transition.origin.top.right class="menu-pop w-60">
                <div class="px-4 py-3" style="border-bottom: 1px solid rgb(var(--c-line))">
                    <p class="truncate text-sm font-bold">{{ $user->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                    <x-badge :tone="$user->isAdmin() ? 'violet' : 'teal'" class="mt-2">{{ $user->labelRole() }}</x-badge>
                </div>
                <a href="{{ route('profile.edit') }}" class="menu-item"><x-icon name="user-circle" size="h-4 w-4" /> Profil saya</a>
                <a href="{{ route('notifications.index') }}" class="menu-item"><x-icon name="bell" size="h-4 w-4" /> Notifikasi</a>
                <a href="{{ route('panduan.index') }}" class="menu-item"><x-icon name="video" size="h-4 w-4" /> Pusat panduan</a>
                <form method="POST" action="{{ route('logout') }}" style="border-top: 1px solid rgb(var(--c-line))">@csrf
                    <button type="submit" class="menu-item" style="color: rgb(var(--t-rose-ink))"><x-icon name="logout" size="h-4 w-4" /> Keluar</button>
                </form>
            </div>
        </div>
    </div>
</header>
