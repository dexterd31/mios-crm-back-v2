<?php

namespace App\Repositories\Contracts;

interface OnlineUserRepository extends RepositoryInterface
{
    public function allByFormAndRole(int $formId, int $roleId) : object;

    public function updateByRRHHId(int $rrhhId, array $data) : object;
}
