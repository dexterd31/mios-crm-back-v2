<?php

namespace App\Jobs;

use App\Managers\DataBaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CreateClients extends Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 9999999;

    protected $formId;

    // public $tries = 100;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($formId)
    {
        $this->formId = $formId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new DataBaseManager)->createClients($this->formId);
    }
}
