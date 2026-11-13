@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<x-page-header eyebrow="Akun" title="Profil saya" subtitle="Kelola informasi akun dan keamanan kata sandi Anda." />

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card card-pad space-y-5" novalidate>
            @csrf @method('PUT')
            <h2 class="card-title">Informasi pribadi</h2>

            <div class="flex flex-wrap items-center gap-4" x-data="fileField(2048)">
                <x-avatar :user="$user" size="h-20 w-20 text-2xl" />
                <div class="min-w-0 flex-1">
                    <label for="foto" class="label">Foto profil</label>
                    <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp" class="input" @change="pilih($event)"
                           @error('foto') aria-invalid="true" aria-describedby="foto-error" @enderror>
                    <p class="hint">JPG, PNG, atau WebP — maksimal 2 MB.</p>
                    <p class="field-error" x-show="galat" x-cloak x-text="galat"></p>
                    @error('foto')<p id="foto-error" class="field-error"><x-icon name="warning" size="mt-px h-3.5 w-3.5" />{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="name" label="Nama lengkap" required :value="$user->name" class="sm:col-span-2" />
                <x-input name="email" type="email" label="Email" required :value="$user->email" />
                <x-input name="no_telp" type="tel" label="Nomor telepon" :value="$user->no_telp" />
                <div>
                    <label class="label">{{ $user->isAdmin() ? 'NIP' : 'NIS' }}</label>
                    <input class="input" value="{{ $user->nis_nip ?? '—' }}" disabled>
                </div>
                @if ($user->isSiswa())
                    <div><label class="label">Kelas</label><input class="input" value="{{ $user->kelas ?? '—' }}" disabled></div>
                @endif
            </div>
            <p class="hint -mt-2">Data akademik (NIS dan kelas) hanya dapat diubah oleh admin sekolah.</p>

            <div class="flex justify-end border-t pt-5" style="border-color: rgb(var(--c-line))">
                <button type="submit" class="btn btn-primary"><x-icon name="check" size="h-4 w-4" /> Simpan profil</button>
            </div>
        </form>

        <form method="POST" action="{{ route('profile.password') }}" class="card card-pad space-y-5" novalidate>
            @csrf @method('PUT')
            <h2 class="card-title flex items-center gap-2"><x-icon name="key" size="h-4 w-4" /> Ubah kata sandi</h2>
            <x-input name="current_password" type="password" label="Kata sandi saat ini" required autocomplete="current-password" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="password" type="password" label="Kata sandi baru" required autocomplete="new-password" hint="Minimal 8 karakter, kombinasi huruf dan angka." />
                <x-input name="password_confirmation" type="password" label="Ulangi kata sandi baru" required autocomplete="new-password" />
            </div>
            <div class="flex justify-end border-t pt-5" style="border-color: rgb(var(--c-line))">
                <button type="submit" class="btn btn-primary">Ubah kata sandi</button>
            </div>
        </form>
    </div>

    <aside class="space-y-6">
        <div class="card card-pad text-center">
            <x-avatar :user="$user" size="mx-auto h-20 w-20 text-2xl" />
            <p class="mt-3 text-lg font-extrabold">{{ $user->name }}</p>
            <p class="text-sm text-muted">{{ $user->email }}</p>
            <x-badge :tone="$user->isAdmin() ? 'violet' : 'teal'" class="mt-3">{{ $user->labelRole() }}</x-badge>
        </div>
        <div class="card card-pad tone-teal">
            <span class="chip mb-3 h-10 w-10"><x-icon name="shield" size="h-5 w-5" /></span>
            <p class="text-sm font-bold">Jaga keamanan akun</p>
            <p class="mt-1 text-sm text-ink-2">Gunakan kata sandi unik dan jangan bagikan kepada siapa pun, termasuk petugas sekolah.</p>
        </div>
    </aside>
</div>
@endsection
