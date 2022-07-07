<?php

namespace App\Jobs;

use App\Managers\DataBaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CreateClients extends Job
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new DataBaseManager)->createClients();
        dispatch($this)->onQueue('create-clients')->delay(Carbon::now()->addSeconds(10));
    }
}
