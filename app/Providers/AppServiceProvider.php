<?php

namespace App\Providers;

use App\Support\ApplicationDatabaseBootstrap;
use App\Support\CloudDatabaseConfig;
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
        CloudDatabaseConfig::apply();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CloudDatabaseConfig::apply();
        PersistentDatabaseConfig::apply();

        try {
            ApplicationDatabaseBootstrap::ensureReady();
        } catch (\Throwable $e) {
            report($e);
        }

        ProductionDriverGuard::apply();
    }
}
