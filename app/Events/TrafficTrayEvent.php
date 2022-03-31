<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TrafficTrayEvent extends Event implements ShouldBroadcast
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @inheritDoc
     */
    public function broadcastOn()
    {
        return ['trafficTrays'];
    }

    public function broadcastAs(){
        return 'trafficTrayStatus';
    }
}
