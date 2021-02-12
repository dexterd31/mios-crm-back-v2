<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';
    protected $PrimaryKey = 'id';
    protected $fillable = ['user_id','name_group'];

    public function campaign(){
        return $this->belongsTo('App\Models\Campaing', 'group_id');
    }
    
    public function form(){
        return $this->hasOne('App\Models\Form','group_id');
    }

    public function user(){
        return $this->hasMany('App\Models\User','id');
    }

}
