<?php

namespace App\Managers;

use App\Jobs\TrafficTrayJob;
use App\Repositories\interfaces\ITrafficTrayConfigRepository;
use App\Repositories\interfaces\ITrafficTrayLogRepository;
use Carbon\Carbon;
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
        $trafficConfigCollection = collect(json_decode($trafficTrayConfig->config));
        $currentTray = $trafficConfigCollection->where('priority', 1)->first();
        if($trafficTrayLog){
            $currenState = json_decode($trafficTrayLog->data);
            $maxPriority = $trafficConfigCollection->max('priority');
            if($currenState->priority >= $maxPriority){
                return;
            }
            //TODO: refactor
            $days = $currenState->days;
            $hours = $currenState->hours;
            $minutes = $currenState->minutes;
            $currentTime = Carbon::now();
            $trafficLogCreatedTime = Carbon::createFromTimestamp($trafficTrayLog->created_at);
            $expectedTime = $trafficLogCreatedTime->addDays($days)->addHours($hours)->addMinutes($minutes);
            if($currentTime->lessThan($expectedTime)){
                return;
            }
            $currentTray = $trafficConfigCollection->where('priority', $currenState->priority+1)->first();
        }
        $this->logRepository->create([
            "traffic_tray_id" => $trafficTrayConfig->id,
            "form_answer_id" => $formAnswerId,
            "data" => $currentTray
        ]);
        //TODO: enviar a pusher y disparar job
        $this->updateTrafficStatusInJob($formAnswerId,$trafficTrayConfig,$currentTray);
    }

    public function updateTrafficStatusInJob(int $formAnswerId, $trafficTrayConfig, $currentTray){
        $config = json_decode($currentTray->config);
        dispatch(new TrafficTrayJob($formAnswerId,$trafficTrayConfig))
            ->delay(now()->days($config->days)->hours($config->hours)->minutes($config->minutes));
    }
}
