@extends('layouts.guest')
@section('title', 'Lupa Kata Sandi')
@section('content')
<div class="mb-7"><h1 class="text-3xl font-extrabold">Lupa kata sandi?</h1><p class="mt-2 text-sm text-ink-2">Masukkan email akun EKSiDU. Jika terdaftar, instruksi reset akan dikirim ke email tersebut.</p></div>
@if(session('status'))<div class="alert tone-green mb-5" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert tone-rose mb-5" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.email') }}" class="space-y-5">@csrf
    <div><label for="email" class="label">Email akun</label><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div>
    <button class="btn btn-primary btn-block" type="submit">Kirim tautan reset</button>
</form>
<p class="mt-6 text-center text-sm text-ink-2"><a class="link" href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
@endsection
