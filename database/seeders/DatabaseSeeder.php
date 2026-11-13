<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seluruh seeder bersifat idempoten: aman dijalankan berulang kali
     * (memakai firstOrCreate, tidak menimpa data/kata sandi yang sudah ada).
     */
    public function run(): void
    {
        $this->call([
            DocumentTypeSeeder::class,
            AdminSeeder::class,
        ]);

        $bolehSeedDemo = (app()->environment(['local', 'testing']) && config('eksidu.akun_demo'))
            || (app()->isProduction() && config('eksidu.seed_demo_production'));

        if ($bolehSeedDemo) {
            $this->call(DemoSeeder::class);
        }
    }
}
