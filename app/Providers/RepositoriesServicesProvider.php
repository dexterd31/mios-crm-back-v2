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
    public function boot(){}

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $interfaces = 'App\Repositories\interfaces';
        $repositories = 'App\Repositories';

        $this->app->bind("$interfaces\ITrafficTrayConfigRepository", "$repositories\TrafficTrayConfigRepository");
        $this->app->bind("$interfaces\ITrafficTrayLogRepository", "$repositories\TrafficTrayLogRepository");
        $this->app->bind("$interfaces\OnlineUserRepository", "$repositories\OnlineUserRepository");
    }
}
