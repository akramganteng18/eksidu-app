<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AmankanBerkas extends Command
{
    protected $signature = 'eksidu:amankan-berkas {--dry-run : Hanya tampilkan rencana tanpa memindahkan apa pun}';

    protected $description = 'Memindahkan berkas pendukung dan foto profil lama dari disk publik (storage/app/public) ke disk privat (storage/app/berkas).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $publik = Storage::disk('public');
        $privat = Storage::disk(config('eksidu.disk_berkas'));

        $dipindah = $sudahAman = $hilang = $gagal = 0;

        // Gabungkan berkas pendukung pengajuan dan foto profil: [label, path]
        $daftar = [];
        foreach (Pengajuan::whereNotNull('file_pendukung')->cursor() as $p) {
            $daftar[] = ["tiket {$p->kode_tiket}", $p->file_pendukung];
        }
        foreach (User::whereNotNull('foto')->cursor() as $u) {
            $daftar[] = ["foto profil {$u->email}", $u->foto];
        }

        foreach ($daftar as [$label, $path]) {
            if ($privat->exists($path)) {
                $sudahAman++;

                continue;
            }

            if (! $publik->exists($path)) {
                $hilang++;
                $this->warn("Berkas hilang untuk {$label}: {$path}");

                continue;
            }

            if ($dry) {
                $this->line("[dry-run] {$label}: {$path}");
                $dipindah++;

                continue;
            }

            $stream = $publik->readStream($path);
            $privat->writeStream($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            // Salinan publik baru dihapus SETELAH salinan privat terverifikasi utuh.
            if ($privat->exists($path) && $privat->size($path) === $publik->size($path)) {
                $publik->delete($path);
                $dipindah++;
            } else {
                $gagal++;
                $this->error("Verifikasi gagal untuk {$label}; salinan publik dibiarkan.");
            }
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Dipindahkan: {$dipindah} | Sudah aman: {$sudahAman} | Hilang: {$hilang} | Gagal: {$gagal}");

        return $gagal > 0 ? self::FAILURE : self::SUCCESS;
    }
}
