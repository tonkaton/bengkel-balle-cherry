<?php

namespace App\Providers;

use App\Models\Produk;
use App\Models\Servis;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama jenis item di kolom *_type log_rekomendasi: "produk" / "servis",
        // bukan nama kelas PHP lengkap.
        Relation::morphMap([
            'produk' => Produk::class,
            'servis' => Servis::class,
        ]);
    }
}
