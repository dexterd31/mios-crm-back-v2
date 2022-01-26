<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $fillable = ['form_id',
                           'notification_type',
                           'name',
                           'activators',
                           'subject',
                           'to',
                           'template_to_send',
                           'rrhh_id',
                           'state',
                           'created_at',
                           'updated_at',
                           'signature',
                           'origin'
                          ];

    public function forms(){
        return $this->belongsTo(Form::class);
    }

    public function notification_type(){
        return $this->hasMany(NotificationsType::class);
    }

    /**
     * Retorna los archivos que tiene la notificación.
     * @author Edwin David Sanchez Balbin
     *
     * @return App\Models\NotificationsAttatchment
     */
    public function notificationAttachments()
    {
        return $this->hasMany(NotificationsAttatchment::class);
    }

    /**
     * Retorna los archivos estaticos de la notificación.
     * @author Edwin David Sanchez Balbin
     *
     * @param array $columns
     * @return App\Models\NotificationsAttatchment
     */
    public function getStaticAttachments(array $columns = ['*'])
    {
        return $this->notificationAttachments()->where('type_attachment', 'static')->get($columns);
    }

    /**
     * Retorna los archivos dinamicos de la notificación.
     * @author Edwin David Sanchez Balbin
     *
     * @param array $columns
     * @return App\Models\NotificationsAttatchment
     */
    public function getDynamicAttachments(array $columns = ['*'])
    {
        return $this->notificationAttachments()->where('type_attachment', 'dynamic')->get($columns);
    }
}
