<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';
    protected $PrimaryKey = 'id';
    protected $fillable = ['name_client','lastname','document','email','phone', 'basic_information'];

    
    public function formanswer(){
        return $this->hasMany('App\Models\FormAnswer', 'client_id');
    }

    public function keyvalue(){
        return $this->belongsTo('App\Models\KeyValue','client_id');
    }
}
