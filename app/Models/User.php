<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $PrimaryKey = 'id';
    protected $fillable = ['id_rhh','state'];

    public function groupuser(){
        return $this->belongsTo('App\Models\GroupUser','user_id');
    }

    public function formanswer(){
        return $this->belongsTo('App\Models\FormAnswer','user_id');
    }

    public function upload(){
        return $this->belongsTo('App\Models\Upload','user_id');
    }

}
