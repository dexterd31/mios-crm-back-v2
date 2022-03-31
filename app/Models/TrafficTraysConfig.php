<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficTraysConfig extends Model
{
    protected $table = 'traffic_trays_config';
    protected $fillable = [
      'tray_id','config'
    ];

    public function tray(){
        return $this->belongsTo(Tray::class,'tray_id','id');
    }
}
