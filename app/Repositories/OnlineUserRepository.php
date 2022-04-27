<?php

namespace App\Repositories;

use App\Models\OnlineUser;
use App\Repositories\Contracts\OnlineUserRepository as OnlineUserInterface;

class OnlineUserRepository extends EloquentRepository implements OnlineUserInterface
{
    public function __construct()
    {
        $this->model = new OnlineUser;
    }

    /**
     * Busca todos los usuarios fitrados por formulario y por rol.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param integer $formId
     * @param integer $roleId
     * @return object
     */
    public function allByFormAndRole(int $formId, int $roleId) : object
    {
        return $this->model->formFilter($formId)->roleFilter($roleId);
    }

    /**
     * Busca el registro por el id de rrhh y actualiza los datos.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param integer $rrhhId
     * @param array $data
     * @return object
     */
    public function updateByRRHHId(int $rrhhId, array $data) : object
    {
        return $this->model->where('rrhh_id', $rrhhId)->update($data);
    }
}
