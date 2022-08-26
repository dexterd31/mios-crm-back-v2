<?php

namespace App\Jobs;

use App\Managers\ReportManager;

class FormReport extends Job
{
    protected $data;
    protected $rrhhIdToNotify;

    public $timeout = 9999999;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data, $rrhhIdToNotify)
    {
        $this->data = $data;
        $this->rrhhIdToNotify = $rrhhIdToNotify;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new ReportManager)->consultReportData($this->data, $this->rrhhIdToNotify);
    }
}
