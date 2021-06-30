<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Services\DataCRMService;
use App\Models\ApiConnection;
use Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->call(function() {
            $formsDataCRM = ApiConnection::where('api_type',10)->where('status',1)->get();
            foreach ($formsDataCRM as $key => $value) {
                DataCRMService::getAccounts($value->form_id);
            }
            Log::info('Ejecutandose minuto a minuto');
            })->everyMinute();
    }
}
