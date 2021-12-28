<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationsType extends Model
{
    protected $table = 'notifications_type';
    protected $primaryKey = 'id';
    protected $fillable = [
                            'notifications_name',
                            'state',
                            'rrhh_id',
                            'created_at',
                            'updated_at',
                          ];
}
