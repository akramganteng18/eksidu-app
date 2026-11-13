<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\TotpAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('two_factor_pending');
        if (! is_array($pending) || now()->timestamp - (int) ($pending['created_at'] ?? 0) > 300) {
            $request->session()->forget('two_factor_pending');
            return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi 2FA berakhir. Silakan masuk kembali.']);
        }
        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TotpAuthenticator $totp): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'min:6', 'max:40']]);
        $pending = $request->session()->get('two_factor_pending');
        abort_unless(is_array($pending) && now()->timestamp - (int) ($pending['created_at'] ?? 0) <= 300, 419, 'Sesi verifikasi 2FA berakhir.');

        $user = User::whereKey($pending['user_id'] ?? 0)->where('role', 'admin')->where('aktif', true)->first();
        abort_unless($user && $user->twoFactorEnabled(), 419, 'Sesi verifikasi 2FA tidak valid.');

        $limiterKey = 'two-factor:' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return back()->withErrors(['code' => 'Terlalu banyak kode salah. Coba lagi dalam ' . RateLimiter::availableIn($limiterKey) . ' detik.']);
        }

        $valid = false;
        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $valid = $totp->verify($secret, $data['code']);
        } catch (\Throwable) {
            $valid = false;
        }

        if (! $valid) {
            $valid = $this->consumeRecoveryCode($user, strtoupper(trim($data['code'])));
        }

        if (! $valid) {
            RateLimiter::hit($limiterKey, 300);
            return back()->withErrors(['code' => 'Kode verifikasi tidak valid. Periksa authenticator atau recovery code.']);
        }

        RateLimiter::clear($limiterKey);
        $remember = (bool) ($pending['remember'] ?? false);
        $request->session()->forget('two_factor_pending');
        Auth::login($user, $remember);
        $request->session()->regenerate();
        ActivityLog::catat($user->id, 'login_2fa', $user->name . ' masuk setelah verifikasi dua faktor.');

        return redirect()->intended(route('dashboard'));
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes || $code === '') return false;

        try {
            $hashes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }
        if (! is_array($hashes)) return false;

        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && Hash::check($code, $hash)) {
                array_splice($hashes, $index, 1);
                $user->forceFill(['two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($hashes)))])->save();
                return true;
            }
        }
        return false;
    }
}
