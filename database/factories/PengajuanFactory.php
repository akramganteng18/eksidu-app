<?php

namespace Database\Factories;

use App\Models\DocumentType;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengajuanFactory extends Factory
{
    protected $model = Pengajuan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jenis_layanan' => 'peminjaman_rapor',
            'document_type_id' => null,
            'keperluan' => 'Keperluan administrasi (data uji).',
            'tanggal_dibutuhkan' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function legalisir(): static
    {
        return $this->state(fn () => [
            'jenis_layanan' => 'legalisir',
            'document_type_id' => DocumentType::factory(),
            'jumlah_lembar' => 2,
        ]);
    }
}
