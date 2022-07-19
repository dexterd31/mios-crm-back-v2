<?php

namespace App\Jobs;

use App\Managers\OutboundManagementManager;
use App\Services\NotificationsService;

class DiffusionBySMS extends Job
{
    protected $clients;
    protected $options;
    protected $outboundManagementId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $outboundManagementId, array $clients, array $options)
    {
        $this->clients = $clients;
        $this->options = $options;
        $this->outboundManagementId = $outboundManagementId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(OutboundManagementManager $outboundManagementManager)
    {
        $outboundManagementManager->sendDiffusionBySMS($this->outboundManagementId, $this->clients, $this->options);
    }
}
