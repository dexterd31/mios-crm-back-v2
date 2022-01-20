<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class BaseRepository implements RepositoryInterface
{
    protected $model;

    public function all()
    {
        return $this->model->all();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function find($id)
    {
        // if (null == $modelFound = $this->model->find($id)) {
        //     throw new ModelNotFoundException("Model not found");
        // }

        // return $modelFound;
        return $this->model->findOrFail($id);
    }
}
