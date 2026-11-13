<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->lexify('???')),
            'nama' => 'Dokumen ' . fake()->unique()->word(),
            'keterangan' => fake()->sentence(),
            'aktif' => true,
            'layanan' => ['legalisir'],
        ];
    }
}
