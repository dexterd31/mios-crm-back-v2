<?php

namespace App\Repositories\interfaces;

interface ITrafficTrayConfigRepository extends IBaseRepository
{
    /**
     * @desc: obtiene las configuraciones por el tray id
     * @author: Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $trayId
     * @return mixed
     */
    public function findByTrayId(int $trayId);
}
