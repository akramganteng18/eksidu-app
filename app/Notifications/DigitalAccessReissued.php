<?php

namespace App\Notifications;

use App\Models\Pengajuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DigitalAccessReissued extends Notification
{
    use Queueable;

    public function __construct(public Pengajuan $pengajuan) {}

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
            'message' => "Akses dokumen digital untuk tiket {$this->pengajuan->kode_tiket} diterbitkan ulang. QR sebelumnya tidak berlaku.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('QR dokumen digital diterbitkan ulang — ' . config('eksidu.nama'))
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line("Akses dokumen digital untuk tiket {$this->pengajuan->kode_tiket} diterbitkan ulang.")
            ->line('QR sebelumnya tidak berlaku. Buka detail pengajuan untuk menampilkan QR yang baru.')
            ->action('Buka pengajuan', route('siswa.pengajuan.show', $this->pengajuan));
    }
}
