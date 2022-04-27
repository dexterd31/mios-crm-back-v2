<?php

namespace App\Http\Controllers;

use App\Managers\TrafficTrayManager;
use App\Repositories\interfaces\ITrafficTrayConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TrafficTraysController extends Controller
{
    private $manager;
    private $repository;

    public function __construct(
        TrafficTrayManager $trafficTrayManager,
        ITrafficTrayConfigRepository $trafficTrayConfigRepository
    )
    {
        $this->manager = $trafficTrayManager;
        $this->repository = $trafficTrayConfigRepository;
    }

    /**
     * @desc retorna configuración de la semaforización según su id
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param $id: traffic tray id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig($id){
        try {
            $config = $this->repository->find($id);
            return $this->successResponse($config);
        } catch (Throwable $th) {
            Log::error("TrafficTraysController@getConfig: {$th->getMessage()}");
            return $this->errorResponse($th->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @desc retorna configuración de la semaforización según su tray_id
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param $trayId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfigByTrayId($trayId){
        try{
            $config = $this->repository->findByTrayId($trayId);
            return $this->successResponse($config);
        }catch (Throwable $th) {
            Log::error("TrafficTraysController@getConfigByTrayId: {$th->getMessage()}");
            return $this->errorResponse($th->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @desc crea una nueva configuración para la semaforización de bandejas
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createConfig(Request $request){
        try {
            $newConfig = $this->manager->newTrafficTray($request->toArray());
            return $this->successResponse($newConfig);
        }catch (Throwable $th) {
            Log::error("TrafficTraysController@create: {$th->getMessage()}");
            return $this->errorResponse($th->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @desc edita la configuración para la semaforización de bandejas
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $id : id de la tabla traffic_trays_config
     * @param Request $request: contiene los campos editables de la tabla traffic_trays_config
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateConfig(int $id, Request $request){
        try{
            $updatedConfig = $this->repository->update($id,$request->toArray());
            return $this->successResponse($updatedConfig);
        }catch (Throwable $th) {
            Log::error("TrafficTraysController@update: {$th->getMessage()}");
            return $this->errorResponse($th->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
