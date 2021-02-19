<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    protected $table = 'group_users';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id','user_id'];

    public function group(){
        return $this->hasMany('App\Models\Group','id');
    }
    
    public function user(){
        return $this->hasMany('App\Models\User','id');
    }
}
