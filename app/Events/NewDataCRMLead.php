<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDataCRMLead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificationId;
    public $formId;
    public $clientId;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($notificationId,$formId,$clientId)
    {
        $this->notificationId = $notificationId; //Para cambiar el estado de la notification cuando le hagan click
        $this->formId = $formId; //Form id para identificar a los agentes del formulario
        $this->clientId = $clientId; //client id de mios
    }

    public function broadcastOn()
    {
        return new Channel('canal');
    }

    public function broadcastAs(){
        return 'evento';
    }
}
