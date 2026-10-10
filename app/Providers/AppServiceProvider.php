<?php

namespace App\Providers;

use App\Services\InstanceSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->booting(function (): void {
            if (! in_array($_SERVER['argv'][1] ?? '', ['config:cache', 'config:clear'], true)) {
                app(InstanceSettings::class)->apply();
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
