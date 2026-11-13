@extends('layouts.guest')
@section('title', 'Atur Ulang Kata Sandi')
@section('content')
<div class="mb-7"><h1 class="text-3xl font-extrabold">Atur kata sandi baru</h1><p class="mt-2 text-sm text-ink-2">Gunakan kata sandi kuat yang belum pernah dipakai pada akun ini.</p></div>
@if($errors->any())<div class="alert tone-rose mb-5" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.update') }}" class="space-y-5">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div><label for="email" class="label">Email</label><input class="input" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required></div>
    <div><label for="password" class="label">Kata sandi baru</label><input class="input" id="password" name="password" type="password" autocomplete="new-password" required></div>
    <div><label for="password_confirmation" class="label">Ulangi kata sandi</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
    <button class="btn btn-primary btn-block" type="submit">Simpan kata sandi baru</button>
</form>
@endsection
