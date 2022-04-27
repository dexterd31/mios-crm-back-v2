<?php

namespace App\Repositories\Contracts;

use App\Repositories\interfaces\IBaseRepository;

interface OnlineUserRepository extends IBaseRepository
{
    public function allByFormAndRole(int $formId, int $roleId) : object;

    public function updateByRRHHId(int $rrhhId, array $data) : object;
}
