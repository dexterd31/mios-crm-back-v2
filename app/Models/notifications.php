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
}
