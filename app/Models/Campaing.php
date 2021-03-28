<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaing extends Model
{
    protected $table = 'campaings';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id','name_campaign'];

    public function group(){
        return $this->hasMany('App\Models\Group','id');
    }

    public function client(){
        return $this->belongsTo('App\Models\Client','campaign_id');
    }
}
