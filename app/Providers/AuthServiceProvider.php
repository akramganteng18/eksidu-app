<?php

namespace App\Providers;

use App\Models\PanduanMedia;
use App\Models\Pengajuan;
use App\Policies\PanduanMediaPolicy;
use App\Policies\PengajuanPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Pengajuan::class => PengajuanPolicy::class,
        PanduanMedia::class => PanduanMediaPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
