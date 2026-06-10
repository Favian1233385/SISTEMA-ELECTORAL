<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Forzar el uso de HTTPS únicamente cuando el entorno sea de producción
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
