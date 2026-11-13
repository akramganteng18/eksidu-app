<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $cfg = config('eksidu.admin');

        if (User::where('email', $cfg['email'])->exists()) {
            $this->command?->info("Akun admin {$cfg['email']} sudah ada — dilewati (kata sandi tidak diubah).");

            return;
        }

        $password = $cfg['password'];
        $dibuatAcak = false;

        if (empty($password)) {
            if (app()->environment(['local', 'testing'])) {
                $password = config('eksidu.demo_password');
                $this->command?->warn('ADMIN_PASSWORD kosong: memakai kata sandi demo. JANGAN dipakai di produksi.');
            } else {
                $password = Str::random(20);
                $dibuatAcak = true;
            }
        }

        User::create([
            'name'     => $cfg['nama'],
            'email'    => $cfg['email'],
            'password' => Hash::make($password),
            'role'     => 'admin',
            'nis_nip'  => $cfg['nip'] ?: null,
            'aktif'    => true,
        ]);

        $this->command?->info("Akun admin dibuat: {$cfg['email']}");

        if ($dibuatAcak) {
            $this->command?->warn("Kata sandi acak (catat SEKARANG, tidak akan ditampilkan lagi): {$password}");
        }
    }
}
