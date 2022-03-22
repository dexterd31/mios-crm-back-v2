<?php

namespace App\Jobs;

use App\Managers\TrafficTrayManager;

class TrafficTrayJob extends Job
{
    private $trafficTrayManager;
    private $formAnswerId;
    private $trafficTrayConfig;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($formAnswerId, $trafficTrayConfig)
    {
        $this->trafficTrayManager = app(TrafficTrayManager::class);
        $this->formAnswerId = $formAnswerId;
        $this->trafficTrayConfig = $trafficTrayConfig;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->trafficTrayManager->validateTrafficTrayStatus($this->formAnswerId,$this->trafficTrayConfig);
    }
}
