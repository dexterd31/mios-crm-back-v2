<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class notifications_attatchment extends Model
{
    protected $table = 'notifications_attatchment';
    protected $primaryKey = 'id';
    protected $fillable = [
                            'notifications_id',
                            'static_atatchment',
                            'dinamic_atatchment',
                            'route_atatchment',
                            'created_at',
                            'updated_at',
                          ];

    public function notifications(){
        return $this->belongsTo(Notifications::class);
    }
}
