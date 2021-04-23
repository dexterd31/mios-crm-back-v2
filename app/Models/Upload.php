<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $table = 'uploads';
    protected $PrimaryKey = 'id';
    protected $fillable = ['name','form_id','user_id', 'count', 'method'];

    public function user(){
        return $this->hasMany('App\Models\User','user_id');
    }
    public function form(){
        return $this->belongsTo('App\Models\Form','form_id');
    }
}
