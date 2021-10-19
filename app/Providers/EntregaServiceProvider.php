<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EntregaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('App\Services\EntregaServiceInterface', function () {
            return new EntregaService();
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
