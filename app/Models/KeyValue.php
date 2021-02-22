<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyValue extends Model
{
    protected $table = 'key_values';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','client_id','key','value','description'];

    public function client(){
        return $this->hasMany('App\Models\Client','id');
    }
    
    public function form(){
        return $this->belongsTo('App\Models\Form','id');
    }
}
