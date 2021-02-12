<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $table = 'uploads';
    protected $PrimaryKey = 'id';
    protected $fillable = ['user_id'];

    public function user(){
        return $this->hasMany('App\Models\User','id');
    }

}
