<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationsAttatchment extends Model
{
    protected $table = 'notifications_attatchment';
    protected $primaryKey = 'id';
    protected $fillable = [
                            'notifications_id',
                            'static_atachment',
                            'dinamic_atachment',
                            'route_atachment',
                            'created_at',
                            'updated_at',
                          ];

    public function notifications(){
        return $this->belongsTo(Notifications::class);
    }
}
