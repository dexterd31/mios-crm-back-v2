<?php

namespace App\Repositories;

use App\Models\TrafficTraysLog;
use App\Repositories\interfaces\ITrafficTrayLogRepository;

class TrafficTrayLogRepository implements ITrafficTrayLogRepository
{
    private $model;

    public function __construct(TrafficTraysLog $trafficTraysLog)
    {
        $this->model = $trafficTraysLog;
    }

    /**
     * @inheritDoc
     */
    public function all()
    {
        // TODO: Implement all() method.
    }

    /**
     * @inheritDoc
     */
    public function find(int $id)
    {
        // TODO: Implement find() method.
    }

    /**
     * @inheritDoc
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        return $this->model->whereId($id)
                            ->update($data);
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    public function getTrafficLog(int $trafficTrayId,int $formAnswerId,$enabled = 1){
        return $this->model->where('traffic_tray_id',$trafficTrayId)
            ->where('form_answer_id',$formAnswerId)
            ->where('enabled',$enabled)
            ->get()->last();
    }
}
