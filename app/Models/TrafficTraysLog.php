<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficTraysLog extends Model
{
    protected $table = 'traffic_trays_log';
    protected $fillable = [
        'tray_id','config'
    ];

    public function trayConfig(){
        return $this->belongsTo(TrafficTraysConfig::class, 'traffic_tray_id');
    }
}
