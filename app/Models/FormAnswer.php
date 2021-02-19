<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormAnswer extends Model
{
    protected $table = 'form_answers';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','user_id', 'client_id','channel_id','structure_answer'];

    public function form(){
       return $this->hasMany('App\Models\Form','id');
    }

    public function client(){
        return $this->hasMany('App\Models\Client', 'id');
    }

    public function user(){
        return $this->hasMany('App\Models\User','id');
    }

    public function channel(){
        return $this->hasMany('App\Models\Channel', 'id');
    }
}
