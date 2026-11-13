@extends('layouts.guest')
@section('title', 'Verifikasi Dua Faktor')
@section('content')
<div class="mb-7"><span class="chip tone-teal mb-4 h-12 w-12"><x-icon name="shield" size="h-6 w-6"/></span><h1 class="text-3xl font-extrabold">Verifikasi keamanan</h1><p class="mt-2 text-sm text-ink-2">Masukkan kode 6 digit dari aplikasi authenticator Admin. Anda juga dapat menggunakan recovery code sekali pakai.</p></div>
@if($errors->any())<div class="alert tone-rose mb-5" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('two-factor.challenge.store') }}" class="space-y-5">@csrf
    <div><label for="code" class="label">Kode verifikasi / recovery code</label><input class="input text-center text-xl font-extrabold tracking-[.2em]" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" maxlength="40" autofocus required></div>
    <button class="btn btn-primary btn-block" type="submit">Verifikasi dan masuk</button>
</form>
<p class="mt-6 text-center text-sm text-ink-2"><a href="{{ route('login') }}" class="link">Batalkan dan kembali ke login</a></p>
@endsection
