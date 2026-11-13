<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\TotpAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $pendingSecret = $request->session()->get('two_factor_setup_secret');
        $secret = $pendingSecret ? Crypt::decryptString($pendingSecret) : null;
        $qrSvg = $secret
            ? (string) QrCode::format('svg')->size(220)->margin(1)->generate(app(TotpAuthenticator::class)->provisioningUri($secret, $user->email))
            : null;

        return view('admin.security.two-factor', [
            'enabled' => $user->twoFactorEnabled(),
            'secret' => $secret,
            'qrSvg' => $qrSvg,
        ]);
    }

    public function begin(Request $request, TotpAuthenticator $totp): RedirectResponse
    {
        abort_unless(! $request->user()->twoFactorEnabled(), 409, '2FA sudah aktif.');
        $request->session()->put('two_factor_setup_secret', Crypt::encryptString($totp->generateSecret()));
        return back()->with('status', 'Secret 2FA dibuat. Pindai QR atau masukkan secret secara manual ke aplikasi authenticator.');
    }

    public function confirm(Request $request, TotpAuthenticator $totp): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $encrypted = $request->session()->get('two_factor_setup_secret');
        abort_unless($encrypted, 419, 'Sesi penyiapan 2FA kedaluwarsa. Mulai kembali.');
        $secret = Crypt::decryptString($encrypted);

        if (! $totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'Kode authenticator tidak cocok. Periksa waktu pada ponsel dan coba lagi.']);
        }

        $recoveryCodes = [];
        $recoveryHashes = [];
        for ($i = 0; $i < 8; $i++) {
            $plain = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
            $recoveryCodes[] = $plain;
            $recoveryHashes[] = Hash::make($plain);
        }

        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryHashes)),
        ])->save();
        $request->session()->forget('two_factor_setup_secret');

        ActivityLog::catat($user->id, 'two_factor_enabled', 'Mengaktifkan autentikasi dua faktor.');

        return redirect()->route('admin.security.two-factor')
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', '2FA berhasil diaktifkan. Simpan recovery code sekarang; kode hanya ditampilkan sekali.');
    }

    public function disable(Request $request, TotpAuthenticator $totp): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'digits:6'],
        ]);
        $user = $request->user();
        abort_unless($user->twoFactorEnabled(), 409, '2FA belum aktif.');
        $secret = Crypt::decryptString($user->two_factor_secret);

        if (! $totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'Kode authenticator tidak cocok. 2FA belum dinonaktifkan.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
        ActivityLog::catat($user->id, 'two_factor_disabled', 'Menonaktifkan autentikasi dua faktor.');

        return back()->with('status', '2FA berhasil dinonaktifkan.');
    }
}
