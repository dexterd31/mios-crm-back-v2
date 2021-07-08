<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class NewDataCRMLead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formId;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($formId)
    {
        $this->formId = $formId; //Form id para identificar a los agentes del formulario
    }


    public function broadcastOn()
    {
        return new Channel('notification-lead');
    }

    public function broadcastAs(){
        return 'update';
    }
}
