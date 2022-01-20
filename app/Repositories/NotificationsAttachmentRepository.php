<?php

namespace App\Repositories;

use App\Models\NotificationsAttatchment;

class NotificationsAttachmentRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new NotificationsAttatchment();
    }

    public function allByNotification(int $notificationId)
    {
        return $this->model->where('notifications_id', $notificationId)->get();
    }
}
