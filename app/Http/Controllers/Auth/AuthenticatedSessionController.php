<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $kunci = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        // Verifikasi kredensial terlebih dahulu tanpa membuat sesi login.
        $user = User::where('email', $credentials['email'])->first();
        $passwordValid = $user && Hash::check($credentials['password'], $user->password);
        $berhasil = $passwordValid && $user->aktif;

        if (! $berhasil) {
            RateLimiter::hit($kunci, 60);

            // Status nonaktif hanya diungkap bila kata sandinya BENAR.
            if ($user && ! $user->aktif && $passwordValid) {
                throw ValidationException::withMessages([
                    'email' => 'Akun Anda dinonaktifkan. Hubungi Admin/TU sekolah.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        // Admin yang mengaktifkan 2FA harus menyelesaikan tantangan sebelum Auth::login.
        if ($user->twoFactorEnabled()) {
            $request->session()->put('two_factor_pending', [
                'user_id' => $user->id,
                'remember' => $request->boolean('remember'),
                'created_at' => now()->timestamp,
            ]);
            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        ActivityLog::catat($user->id, 'login', "{$user->name} masuk ke sistem.");

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            ActivityLog::catat($user->id, 'logout', "{$user->name} keluar dari sistem.");
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dengan aman.');
    }
}
