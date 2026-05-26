<?php

namespace App\Providers;

use App\Support\ApplicationDatabaseBootstrap;
use App\Support\PersistentDatabaseConfig;
use App\Support\ProductionDriverGuard;
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
        PersistentDatabaseConfig::apply();

        try {
            ApplicationDatabaseBootstrap::ensureReady();
        } catch (\Throwable $e) {
            report($e);
        }

        ProductionDriverGuard::apply();
    }
}
