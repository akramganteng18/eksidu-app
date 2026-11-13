<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Nama bulan/hari dan diffForHumans() berbahasa Indonesia.
        Carbon::setLocale(config('app.locale'));

        // Tampilan paginasi bertema EKSiDU (resources/views/vendor/pagination); hanya untuk paginate() biasa.
        Paginator::defaultView('vendor.pagination.eksidu');

        // Aturan kata sandi baku untuk seluruh formulir: minimal 8 karakter, huruf + angka.
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
