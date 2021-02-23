<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $table = 'uploads';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','user_id'];

    public function user(){
        return $this->hasMany('App\Models\User','id');
    }
    public function form(){
        return $this->belongsTo('App\Models\Form','id');
    }
}
