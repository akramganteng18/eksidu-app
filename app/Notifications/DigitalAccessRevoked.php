<?php

namespace App\Notifications;

use App\Models\Pengajuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DigitalAccessRevoked extends Notification
{
    use Queueable;

    public function __construct(public Pengajuan $pengajuan, public string $reason = 'Akses dokumen digital dinonaktifkan oleh petugas.') {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (config('eksidu.notifikasi_email') && filled($notifiable->email)) $channels[] = 'mail';
        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'pengajuan_id' => $this->pengajuan->id,
            'kode_tiket' => $this->pengajuan->kode_tiket,
            'jenis_layanan' => $this->pengajuan->labelJenisLayanan(),
            'status' => $this->pengajuan->status,
            'status_label' => $this->pengajuan->statusLabel(),
            'message' => "Akses digital untuk tiket {$this->pengajuan->kode_tiket} telah dicabut. {$this->reason}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Akses dokumen digital diperbarui — ' . config('eksidu.nama'))
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line("Akses digital untuk tiket {$this->pengajuan->kode_tiket} telah dicabut.")
            ->line($this->reason)
            ->action('Lihat pengajuan', route('siswa.pengajuan.show', $this->pengajuan));
    }
}
