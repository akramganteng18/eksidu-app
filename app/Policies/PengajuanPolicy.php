<?php

namespace App\Policies;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PengajuanPolicy
{
    /**
     * Admin boleh melihat semua; siswa hanya miliknya sendiri.
     * Untuk milik orang lain dijawab 404 (bukan 403) agar keberadaan tiket
     * tidak bisa ditebak lewat ID berurutan.
     */
    public function view(User $user, Pengajuan $pengajuan): Response
    {
        return $user->isAdmin() || $pengajuan->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function viewBerkas(User $user, Pengajuan $pengajuan): Response
    {
        return $this->view($user, $pengajuan);
    }
}
