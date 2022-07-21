<?php

namespace App\Jobs;

use App\Managers\OutboundManagementManager;

class DiffusionByVoice extends Job
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
        $outboundManagementManager->sendDiffusionByVoice($this->outboundManagementId, $this->clients, $this->options);
    }
}
