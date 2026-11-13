<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Registrasi mandiri hanya aktif bila EKSIDU_REGISTRASI_PUBLIK=true.
     * Secara bawaan akun siswa dibuat oleh Admin/TU (Master Data Siswa).
     */
    private function pastikanDibuka(): void
    {
        abort_unless(config('eksidu.registrasi_publik'), 404);
    }

    public function create()
    {
        $this->pastikanDibuka();

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->pastikanDibuka();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'nis_nip' => ['required', 'string', 'max:50', 'unique:users,nis_nip'],
            'kelas' => ['required', 'string', 'max:50'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'nis_nip.unique' => 'NIS ini sudah terdaftar. Jika ini NIS Anda, gunakan halaman Masuk.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            // PENTING: role di-hardcode 'siswa', TIDAK diambil dari $request,
            // sehingga tidak bisa dinaikkan menjadi admin lewat manipulasi form.
            'role' => 'siswa',
            'nis_nip' => $validated['nis_nip'],
            'kelas' => $validated['kelas'],
            'no_telp' => $validated['no_telp'] ?? null,
            'aktif' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLog::catat($user->id, 'register', "{$user->name} mendaftar akun baru sebagai siswa.");

        return redirect()->route('dashboard')
            ->with('status', 'Pendaftaran berhasil! Selamat datang di ' . config('eksidu.nama') . '.');
    }
}
