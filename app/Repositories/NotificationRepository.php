<?php

namespace App\Repositories;

use App\Models\Notifications;

class NotificationRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Notifications();
    }

    /**
     * Retorna las notificaciondes de un formulario.
     * @author Edwin David Sanchez Balbin
     *
     * @param integer $formId
     * @return App\Models\Notifications
     */ 
    public function allByForm(int $formId)
    {
        return $this->model->where('form_id',$formId)->get();
    }
}
