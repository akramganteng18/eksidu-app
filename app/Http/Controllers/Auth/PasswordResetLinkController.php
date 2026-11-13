<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        // Respons dibuat generik agar keberadaan akun tidak dapat ditebak melalui formulir.
        // Akun nonaktif tidak boleh memperoleh tautan pemulihan.
        $email = $request->string('email')->toString();
        $activeUserExists = User::where('email', $email)->where('aktif', true)->exists();
        $status = $activeUserExists ? Password::sendResetLink(['email' => $email]) : Password::INVALID_USER;

        // Pesan harus identik pada akun ada/tidak ada agar tidak menjadi oracle enumerasi.
        return back()->with('status', 'Jika alamat email terdaftar dan aktif, instruksi reset kata sandi akan dikirim. Periksa kotak masuk atau spam.');
    }
}
