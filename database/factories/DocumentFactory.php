<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'document_type_id' => null,
            'uploaded_by' => null,
            'title' => fake()->sentence(3),
            'academic_year' => '2026/2027',
            'semester' => 1,
            'graduation_year' => null,
            'disk' => 'berkas',
            'file_path' => 'repository/dummy.pdf',
            'original_filename' => 'dummy.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'checksum_sha256' => hash('sha256', 'dummy'),
            'status' => 'available',
        ];
    }
}
