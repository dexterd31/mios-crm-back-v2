<?php

namespace App\Jobs;

use App\Managers\TrafficTrayManager;

class TrafficTrayJob extends Job
{
    private $trafficTrayManager;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->trafficTrayManager = app(TrafficTrayManager::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->trafficTrayManager->updateTrafficStatusInAnswer();
    }
}
