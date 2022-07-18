<?php

namespace App\Jobs;

use App\Managers\OutboundManagementManager;

class DiffusionByEmail extends Job
{
    protected $clients;
    protected $options;
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
        $outboundManagementManager->sendDiffusionByEmail($this->clients, $this->options);
    }
}
