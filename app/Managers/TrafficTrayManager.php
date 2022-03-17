<?php

namespace App\Managers;

use App\Repositories\interfaces\ITrafficTrayConfigRepository;

class TrafficTrayManager
{
    private $repository;

    public function __construct(ITrafficTrayConfigRepository $repository){
        $this->repository = $repository;
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
        return $this->repository->create($configTrayArray);
    }

    public function validateTrafficStatus(){

    }

    public function updateTrafficStatusInAnswer(){
        //TODO: crear lógica para actualizar que se pueda usar en job y mediante request
    }
}
