<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoriesServicesProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Repositories\interfaces\ITrafficTrayConfigRepository', 'App\Repositories\TrafficTrayConfigRepository');
        $this->app->bind('App\Repositories\interfaces\ITrafficTrayLogRepository', 'App\Repositories\TrafficTrayLogRepository');
    }
}
