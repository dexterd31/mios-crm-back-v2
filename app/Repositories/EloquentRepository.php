<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;

class EloquentRepository implements RepositoryInterface
{
    protected $model;

    public function all() : object
    {
        return $this->model->all();
    }

    public function find(int $id) : object
    {
        return $this->model->find($id);
    }

    public function create(array $data) : object
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data) : object
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id)
    {
        return $this->model->destroy($id);
    }
}
