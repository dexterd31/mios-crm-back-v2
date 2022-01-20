<?php

namespace App\Repositories;

use App\Models\NotificationsType;

class NotificationsTypeRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new NotificationsType();
    }
}
