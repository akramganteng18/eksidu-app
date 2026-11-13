<?php

namespace App\Notifications;

use App\Models\Pengajuan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PhysicalCodeReissued extends Notification
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
            'message' => "Kode verifikasi fisik untuk tiket {$this->pengajuan->kode_tiket} telah diperbarui. Kode lama tidak berlaku; buka detail pengajuan untuk melihat kode baru.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Kode verifikasi fisik diperbarui — ' . config('eksidu.nama'))
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line("Kode verifikasi fisik untuk tiket {$this->pengajuan->kode_tiket} telah diperbarui.")
            ->line('Kode lama tidak berlaku. Buka detail pengajuan untuk melihat kode baru.')
            ->action('Buka pengajuan', route('siswa.pengajuan.show', $this->pengajuan));
    }
}
