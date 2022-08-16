<?php

namespace App\Jobs;

use App\Managers\ReportManager;

class DeleteReport extends Job
{
    protected $filename;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new ReportManager)->deleteReport($this->filename);
    }
}
