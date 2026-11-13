<?php

namespace App\Notifications;

use App\Models\Pengajuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PengajuanStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public Pengajuan $pengajuan)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (config('eksidu.notifikasi_email') && filled($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pengajuan = $this->pengajuan;
        $url = $notifiable->isAdmin()
            ? route('admin.pengajuan.show', $pengajuan)
            : route('siswa.pengajuan.show', $pengajuan);

        $mail = (new MailMessage)
            ->subject('Pembaruan pengajuan ' . $pengajuan->kode_tiket . ' — ' . config('eksidu.nama'))
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Status pengajuan ' . $pengajuan->kode_tiket . ' sekarang: ' . $pengajuan->statusLabel() . '.');

        if (filled($pengajuan->catatan_admin)) {
            $mail->line('Catatan petugas: ' . $pengajuan->catatan_admin);
        }

        return $mail->action('Lihat pengajuan', $url)
            ->line('Pesan ini dikirim otomatis oleh ' . config('eksidu.nama') . '.');
    }

    public function toArray(object $notifiable): array
    {
        $pengajuan = $this->pengajuan;
        $prefix = "Pengajuan {$pengajuan->kode_tiket} ({$pengajuan->labelJenisLayanan()})";

        $message = match ($pengajuan->status) {
            'pending' => "{$prefix} berhasil diterima dan sedang menunggu verifikasi petugas TU.",
            'diproses' => "{$prefix} sedang diproses oleh petugas TU.",
            'approved' => "{$prefix} telah disetujui dan siap diambil. Silakan tunjukkan QR Code Anda ke petugas TU.",
            'rejected' => "{$prefix} ditolak." . ($pengajuan->catatan_admin
                ? " Alasan: {$pengajuan->catatan_admin}"
                : ' Silakan hubungi petugas TU untuk informasi lebih lanjut.'),
            'selesai' => "{$prefix} telah selesai. Terima kasih telah menggunakan layanan " . config('eksidu.nama') . '.',
            default => "{$prefix} telah diperbarui statusnya menjadi \"{$pengajuan->statusLabel()}\".",
        };

        return [
            'pengajuan_id' => $pengajuan->id,
            'kode_tiket' => $pengajuan->kode_tiket,
            'jenis_layanan' => $pengajuan->labelJenisLayanan(),
            'status' => $pengajuan->status,
            'status_label' => $pengajuan->statusLabel(),
            'catatan_admin' => $pengajuan->catatan_admin,
            'message' => $message,
        ];
    }
}
