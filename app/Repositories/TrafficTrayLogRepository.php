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
        // TODO: Implement create() method.
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        // TODO: Implement update() method.
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
    public function getTrafficLog(int $trafficTrayId,int $formAnswerId){
        return $this->model->where('id',$trafficTrayId)->where('form_answer_id',$formAnswerId)->get()->last();
    }
}
