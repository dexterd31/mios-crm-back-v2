<?php

namespace App\Managers;

use App\Events\TrafficTrayEvent;
use App\Jobs\TrafficTrayJob;
use App\Models\FormAnswersTray;
use App\Repositories\interfaces\ITrafficTrayConfigRepository;
use App\Repositories\interfaces\ITrafficTrayLogRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

class TrafficTrayManager
{
    private $configRepository;
    private $logRepository;
    private $formAnswersTray;

    public function __construct(
        ITrafficTrayConfigRepository $configRepository,
        ITrafficTrayLogRepository $logRepository,
        FormAnswersTray $formAnswersTray
    ){
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
        $this->formAnswersTray = $formAnswersTray;
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

    /**
     * @desc Crea o actualiza el estado de la semaforización de la bandeja
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $formAnswerId: id de la respuesta del formulario
     * @param $trafficTrayConfig: configuración de la semaforización de la bandeja en caso de que esta se encuentre habilitada
     * @return mixed|void
     */
    public function validateTrafficTrayStatus(int $formAnswerId,$trafficTrayConfig){
        if(!$this->validateRelTrayAnswer($trafficTrayConfig->tray_id,$formAnswerId)){
            return;
        }
        $trafficTrayLog = $this->getTrafficTrayLog($trafficTrayConfig->id,$formAnswerId);
        $trafficConfigCollection = collect(json_decode($trafficTrayConfig->config));
        $currentTray = $trafficConfigCollection->where('priority', 1)->first();
        if($trafficTrayLog){
            $currentState = json_decode($trafficTrayLog->data);
            $currentState->tray_id = $trafficTrayConfig->tray_id;
            $maxPriority = $trafficConfigCollection->max('priority');
            if($currentState->priority >= $maxPriority){
                return $currentState;
            }
            $days = $currentState->time->days;
            $hours = $currentState->time->hours;
            $minutes = $currentState->time->minutes;
            $currentTime = Carbon::now();
            $trafficLogCreatedTime = Carbon::createFromTimeString($trafficTrayLog->created_at);
            $expectedTime = $trafficLogCreatedTime->addDays($days)->addHours($hours)->addMinutes($minutes);
            if($currentTime->lessThan($expectedTime)){
                return $currentState;
            }
            $currentTray = $trafficConfigCollection->where('priority', $currentState->priority+1)->first();
            $trafficTrayLog->state = 0;
            $this->logRepository->update($trafficTrayLog->id,["enabled" => 0]);
        }
        $currentTray->formAnswerId = $formAnswerId;
        $currentTray->tray_id = $trafficTrayConfig->tray_id;
        $this->logRepository->create([
            "traffic_tray_id" => $trafficTrayConfig->id,
            "form_answer_id" => $formAnswerId,
            "data" => json_encode($currentTray)
        ]);
        $this->emitCurrentTrayInPusher($currentTray);
        $this->updateTrafficStatusInJob($formAnswerId,$trafficTrayConfig,$currentTray);
    }

    /**
     * @desc Crea el job con el delay para validar el estado de la bandeja
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $formAnswerId: id de la respuesta del formulario
     * @param $trafficTrayConfig: configuración de la semaforización de la bandeja en caso de que esta se encuentre habilitada
     * @param $currentTray: configuración de la bandeja actual
     * @return void
     */
    public function updateTrafficStatusInJob(int $formAnswerId, $trafficTrayConfig, $currentTray):void{
        $time = $currentTray->time;
        Queue::later(Carbon::now()->addDays($time->days)->addHours($time->hours)->addMinutes($time->minutes),
            new TrafficTrayJob($formAnswerId,$trafficTrayConfig));
    }

    /**
     * @desc Obtiene el último dato de la semaforización según si su estado es habilitado (1) o deshabilitado (0).
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $trafficTrayConfigId: id de la configuración de la bandeja (tabla traffic_trays_config)
     * @param int $formAnswerId: id de la respuesta del formulario
     * @param int $enabled: estado del registro en la tabla traffic_trays_log
     * @return mixed
     */
    public function getTrafficTrayLog(int $trafficTrayConfigId,int $formAnswerId,int $enabled = 1){
        $response = $this->logRepository->getTrafficLog($trafficTrayConfigId,$formAnswerId,$enabled);
        return $response;
    }

    /**
     * @desc valida un registro en la tabla traffic_trays_log, si este existe lo deshabilita.
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $formAnswerId: id de la respuesta del formulario
     * @param int $trafficTrayConfigId: id de la configuración de la bandeja (tabla traffic_trays_config)
     * @return void
     */
    public function disableTrafficTrayLog(int $formAnswerId,int $trafficTrayConfigId):void{
        $trafficTrayLog = $this->getTrafficTrayLog($trafficTrayConfigId,$formAnswerId);
        if($trafficTrayLog){
            $this->logRepository->update($trafficTrayLog->id,["enabled" => 0]);
        }
    }

    /**
     * @desc Valida si el registro se encuentra aún en la bandeja
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $trayId: id  de la bandeja
     * @param int $formAnswerId: id de la respuesta del formulario
     * @return mixed
     */
    private function validateRelTrayAnswer(int $trayId, int $formAnswerId){
        return $this->formAnswersTray->where('form_answer_id',$formAnswerId)->where('tray_id',$trayId)->first();
    }

    /**
     * @desc Emíte el valor actual de la semaforización en pusher
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param $currentTray: objeto con el valor actual de la semaforización
     * @return void
     */
    private function emitCurrentTrayInPusher($currentTray):void{
        broadcast(new TrafficTrayEvent($currentTray))->toOthers();
    }
}
