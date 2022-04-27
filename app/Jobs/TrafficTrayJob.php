<?php

namespace App\Jobs;

use App\Managers\TrafficTrayManager;
use Illuminate\Support\Facades\Log;

class TrafficTrayJob extends Job
{
    protected $trafficTrayManager;
    protected $formAnswerId;
    protected $trafficTrayConfig;

    /**
     * @desc Create a new job instance.
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param $formAnswerId: id de la respuesta del formulario
     * @param $trafficTrayConfig: configuración de semaforización de la bandeja
     * @return void
     */
    public function __construct($formAnswerId, $trafficTrayConfig)
    {
        $this->trafficTrayManager = app(TrafficTrayManager::class);
        $this->formAnswerId = $formAnswerId;
        $this->trafficTrayConfig = $trafficTrayConfig;
    }

    /**
     * @desc Execute the job.
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @return void
     */
    public function handle()
    {
        $this->trafficTrayManager->validateTrafficTrayStatus($this->formAnswerId,$this->trafficTrayConfig);
    }
}
