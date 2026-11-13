<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Aset Vite (public/build) tidak diperlukan untuk menguji perilaku server.
        $this->withoutVite();
    }

    protected function buatAdmin(array $atribut = []): User
    {
        return User::factory()->admin()->create($atribut);
    }

    protected function buatSiswa(array $atribut = []): User
    {
        return User::factory()->create($atribut);
    }
}
