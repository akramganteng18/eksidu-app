<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        // PHPUnit memakai key sementara yang dibuat saat runtime, bukan APP_KEY
        // statis atau APP_KEY instalasi pengguna. Trait ini hanya dipakai test.
        $testKey = 'base64:'.base64_encode(random_bytes(32));
        putenv('APP_KEY='.$testKey);
        $_ENV['APP_KEY'] = $testKey;
        $_SERVER['APP_KEY'] = $testKey;

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
