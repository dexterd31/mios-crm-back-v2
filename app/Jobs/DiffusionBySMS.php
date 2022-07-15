<?php

namespace App\Jobs;

use App\Managers\OutboundManagementManager;
use App\Services\NotificationsService;

class DiffusionBySMS extends Job
{
    protected $clients;
    protected $startHour;
    protected $endHour;
    protected $days;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $clients, string $startHour, string $endHour, array $days)
    {
        $this->clients = $clients;
        $this->startHour = $startHour;
        $this->endHour = $endHour;
        $this->days = $days;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(OutboundManagementManager $outboundManagementManager)
    {
        $outboundManagementManager->sendDiffusionBySMS($this->clients, $this->startHour, $this->endHour, $this->days);
    }
}
