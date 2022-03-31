<?php

namespace App\Repositories;

use App\Models\TrafficTraysConfig;
use App\Models\TrafficTraysLog;
use App\Repositories\interfaces\ITrafficTrayConfigRepository;

class TrafficTrayConfigRepository implements ITrafficTrayConfigRepository
{
    private $model;

    public function __construct(TrafficTraysConfig $traysConfigModel)
    {
        $this->model = $traysConfigModel;
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
        return $this->model->whereId($id)->get();
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
        return $this->model->whereId($id)->update($data);
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
    public function findByTrayId(int $trayId)
    {
        return $this->model->where('tray_id',$trayId)->get();
    }
}
