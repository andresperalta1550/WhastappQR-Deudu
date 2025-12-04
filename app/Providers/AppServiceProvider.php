<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Libraries\DebtorFallbackResolver::class,
            function ($app) {
                return new \App\Libraries\DebtorFallbackResolver(
                    [
                        $app->make(\App\Libraries\Oracle\OracleClient::class),
                        $app->make(\App\Libraries\Aquila\AquilaClient::class),
                    ]
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
