<?php

namespace App\Jobs;

use App\Services\NotificationsService;

class DiffusionBySMS extends Job
{
    protected $clients;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $clients)
    {
        $this->clients = $clients;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(NotificationsService $notificationsService)
    {
        foreach ($this->clients as $client) {
            $notificationsService->sendSMS($client['message'], [$client['diffusion']]);
        }
    }
}
