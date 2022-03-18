<?php

namespace App\Managers;

use App\Repositories\interfaces\ITrafficTrayConfigRepository;
use App\Repositories\interfaces\ITrafficTrayLogRepository;
use Illuminate\Support\Facades\Log;

class TrafficTrayManager
{
    private $configRepository;
    private $logRepository;

    public function __construct(
        ITrafficTrayConfigRepository $configRepository,
        ITrafficTrayLogRepository $logRepository
    ){
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * @desc Crea la configuración para la semaforización de la bandeja
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param array $request: arreglo que contiene los datos de l bandeja y la configuración de la semaforización
     * @return mixed
     */
    public function newTrafficTray(array $request){
        $configTrayArray = [
            'tray_id' => $request['tray_id'],
            'config' => json_encode($request['traffic']),
        ];
        return $this->configRepository->create($configTrayArray);
    }

    public function validateTrafficTrayStatus(int $formAnswerId,$trafficTrayConfig){
        $trafficTrayLog = $this->logRepository->getTrafficLog($trafficTrayConfig->id,$formAnswerId);
        if($trafficTrayLog){
            $currenState = $trafficTrayLog->data
            $this->updateTrafficStatusInAnswer();
        }
    }

    public function updateTrafficStatusInAnswer($trayConfigData){
        //TODO: crear lógica para actualizar que se pueda usar en job y mediante request
    }
}
