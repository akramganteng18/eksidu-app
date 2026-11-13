<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Memutus sesi pengguna yang akunnya dinonaktifkan Admin SETELAH ia login.
     * Tanpa ini, akun nonaktif tetap bisa memakai sesi lama sampai kedaluwarsa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->aktif) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Hubungi Admin/TU sekolah.',
            ]);
        }

        return $next($request);
    }
}
