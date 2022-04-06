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

    public function allByFormAndRole(int $formId, int $roleId) : object
    {
        return $this->model->formFilter($formId)->roleFilter($roleId);
    }

    public function updateByRRHHId(int $rrhhId, array $data) : object
    {
        return $this->model->where('rrhh_id', $rrhhId)->update($data);
    }
}
