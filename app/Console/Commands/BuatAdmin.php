<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class BuatAdmin extends Command
{
    protected $signature = 'eksidu:buat-admin
                            {email : Email akun admin baru}
                            {--nama= : Nama lengkap (akan ditanyakan bila kosong)}
                            {--nip= : NIP (opsional)}';

    protected $description = 'Membuat akun Admin/Petugas TU baru. Kata sandi dimasukkan secara tersembunyi, tidak lewat argumen.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Email {$email} sudah terdaftar.");

            return self::FAILURE;
        }

        $nama = $this->option('nama') ?: $this->ask('Nama lengkap');
        $password = (string) $this->secret('Kata sandi (min. 8 karakter, kombinasi huruf dan angka)');
        $ulang = (string) $this->secret('Ulangi kata sandi');

        $cek = Validator::make(
            ['name' => $nama, 'password' => $password, 'password_confirmation' => $ulang],
            ['name' => ['required', 'string', 'max:255'], 'password' => ['required', 'confirmed', Password::defaults()]]
        );

        if ($cek->fails()) {
            foreach ($cek->errors()->all() as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $nama,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'nis_nip' => $this->option('nip') ?: null,
            'aktif' => true,
        ]);

        $this->info("Akun admin {$email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
