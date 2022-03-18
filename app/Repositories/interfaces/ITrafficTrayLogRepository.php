<?php

namespace App\Repositories\interfaces;

interface ITrafficTrayLogRepository extends IBaseRepository
{
    /**
     * @desc: obtiene el ultimo registro de la trabla traffic_trays_log
     * @author: Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $trafficTrayId
     * @param int $formAnswerId
     * @return mixed
     */
    public function getTrafficLog(int $trafficTrayId,int $formAnswerId);
}
