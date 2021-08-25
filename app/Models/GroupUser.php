<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    protected $table = 'group_users';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id', 'rrhh_id'];

    public function group(){
        return $this->hasMany('App\Models\Group','id');
    }
}
