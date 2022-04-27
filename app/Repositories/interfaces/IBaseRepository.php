<?php

namespace App\Repositories\interfaces;

interface IBaseRepository
{
    /**
     * @desc retorna todos los recursos
     * @author Juan Pablo Camargo Vanegas
     * @return mixed
     */
    public function all();

    /**
     * @desc retorna un recurso según su id
     * @author Juan Pablo Camargo Vanegas
     * @param int $id
     * @return mixed
     */
    public function find(int $id);

    /**
     * @desc crea un nuevo recurso
     * @author Juan Pablo Camargo Vanegas
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * @desc actualiza un recurso existente
     * @author Juan Pablo Camargo Vanegas
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);

    /**
     * @desc elimina un recurso
     * @author Juan Pablo Camargo Vanegas
     * @param int $id
     * @return mixed
     */
    public function delete(int $id);
}
