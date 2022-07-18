<?php

namespace App\Jobs;

use App\Managers\OutboundManagementManager;
use App\Services\NotificationsService;

class DiffusionBySMS extends Job
{
    protected $clients;
    protected $options;
    protected $endHour;
    protected $days;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $clients, array $options)
    {
        $this->clients = $clients;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(OutboundManagementManager $outboundManagementManager)
    {
        $outboundManagementManager->sendDiffusionBySMS($this->clients, $this->options);
    }
}
