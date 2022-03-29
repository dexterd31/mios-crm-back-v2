<?php

namespace App\Providers;

use App\Managers\TrafficTrayManager;
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
        $this->app->singleton(TrafficTrayManager::class, function ($app){
            return new TrafficTrayManager();
        });
    }
}
