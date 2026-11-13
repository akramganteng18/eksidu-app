@extends('layouts.app')
@section('title', 'Keamanan Akun')
@section('content')
<div class="space-y-6">
    <x-page-header eyebrow="Keamanan akun" title="Verifikasi dua faktor Admin" subtitle="Tambahkan kode dari aplikasi authenticator saat Admin/TU masuk ke EKSiDU.">
        <x-badge :tone="$enabled ? 'green' : 'amber'">{{ $enabled ? '2FA Aktif' : '2FA Belum Aktif' }}</x-badge>
    </x-page-header>

    @if(session('recovery_codes'))
        <div class="alert tone-amber block" role="alert">
            <strong>Simpan recovery code ini sekarang.</strong>
            <p class="mt-1 text-sm">Setiap kode hanya dapat digunakan satu kali. Kode tidak dapat ditampilkan kembali setelah halaman ditutup.</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                @foreach(session('recovery_codes') as $code)
                    <code class="rounded-lg border px-3 py-2 text-center font-bold" style="border-color:rgb(var(--c-line));background:rgb(var(--c-surface))">{{ $code }}</code>
                @endforeach
            </div>
        </div>
    @endif

    @if($enabled)
        <div class="card card-pad space-y-3">
            <div class="flex items-center gap-3"><span class="chip tone-green h-11 w-11"><x-icon name="shield" size="h-5 w-5"/></span><div><h2 class="font-extrabold">Verifikasi dua faktor aktif</h2><p class="text-sm text-muted">Diaktifkan {{ auth()->user()->two_factor_enabled_at?->format('d M Y H:i') }}.</p></div></div>
            <p class="text-sm text-ink-2">Setiap login Admin/TU memerlukan kode 6 digit dari authenticator atau satu recovery code. Pastikan waktu perangkat authenticator tersinkron.</p>
            <form method="POST" action="{{ route('admin.security.two-factor.disable') }}" class="grid gap-3 border-t pt-4 sm:grid-cols-2" style="border-color:rgb(var(--c-line))">
                @csrf @method('DELETE')
                <div><label for="current_password" class="label">Kata sandi saat ini</label><input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password" required></div>
                <div><label for="disable_code" class="label">Kode authenticator</label><input id="disable_code" name="code" class="input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></div>
                <div class="sm:col-span-2"><button class="btn btn-secondary" type="submit" data-confirm="Nonaktifkan verifikasi dua faktor untuk akun Admin ini?">Nonaktifkan 2FA</button></div>
            </form>
        </div>
    @elseif($secret && $qrSvg)
        <div class="card card-pad space-y-5">
            <div><h2 class="card-title">1. Hubungkan aplikasi authenticator</h2><p class="mt-1 text-sm text-ink-2">Gunakan Google Authenticator, Microsoft Authenticator, 1Password, atau aplikasi TOTP tepercaya lainnya.</p></div>
            <div class="grid items-center gap-5 md:grid-cols-[240px_1fr]">
                <div class="mx-auto rounded-xl border bg-white p-3" style="border-color:rgb(var(--c-line))">{!! $qrSvg !!}</div>
                <div class="space-y-3">
                    <p class="text-sm font-semibold">Tidak bisa memindai QR? Masukkan secret manual:</p>
                    <code class="block break-all rounded-xl p-3 text-sm font-bold" style="background:rgb(var(--c-surface-2))">{{ $secret }}</code>
                    <p class="text-xs text-muted">Secret ini bersifat rahasia. Jangan kirimkan melalui chat atau tangkapan layar publik.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.security.two-factor.confirm') }}" class="max-w-md space-y-3 border-t pt-4" style="border-color:rgb(var(--c-line))">
                @csrf
                <h3 class="font-bold">2. Konfirmasi kode 6 digit</h3>
                <label for="setup_code" class="label">Kode dari aplikasi authenticator</label>
                <input id="setup_code" name="code" class="input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456" required>
                <button class="btn btn-primary" type="submit">Aktifkan verifikasi dua faktor</button>
            </form>
        </div>
    @else
        <div class="card card-pad space-y-4">
            <h2 class="card-title">Lindungi akun Admin/TU</h2>
            <p class="text-sm text-ink-2">Aktifkan 2FA agar password saja tidak cukup untuk masuk. Secret TOTP disimpan dalam bentuk terenkripsi; recovery code disimpan dalam bentuk hash dan hanya ditampilkan satu kali saat pendaftaran.</p>
            <form method="POST" action="{{ route('admin.security.two-factor.begin') }}">@csrf<button class="btn btn-primary" type="submit"><x-icon name="shield" size="h-4 w-4"/> Mulai penyiapan 2FA</button></form>
        </div>
    @endif
</div>
@endsection
