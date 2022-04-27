<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;

class EloquentRepository implements RepositoryInterface
{
    protected $model;

    /**
     * Retorna todos los registros
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return object
     */
    public function all() : object
    {
        return $this->model->all();
    }

    /**
     * busca un registro por su id.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param integer $id
     * @return object
     */
    public function find(int $id) : object
    {
        return $this->model->find($id);
    }

    /**
     * Crea un registro.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param array $data
     * @return object
     */
    public function create(array $data) : object
    {
        return $this->model->create($data);
    }

    /**
     * Busca un registro por su id y lo actualiza.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param integer $id
     * @param array $data
     * @return object
     */
    public function update(int $id, array $data) : object
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Busca un registro por su id y lo borra.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param integer $id
     * @return void
     */
    public function delete(int $id)
    {
        return $this->model->destroy($id);
    }
}
