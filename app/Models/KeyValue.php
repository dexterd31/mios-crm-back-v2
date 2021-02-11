<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyValue extends Model
{
    protected $table = 'key_values';
    protected $PrimaryKey = 'id';
    protected $fillable = ['client_id','key','value'];

    public function client(){
        return $this->hasMany('App\Models\Client','id');
    }
}
