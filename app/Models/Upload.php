<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $table = 'uploads';
    protected $PrimaryKey = 'id';
    protected $fillable = ['name','form_id','rrhh_id', 'count', 'method'];

    public function form(){
        return $this->belongsTo('App\Models\Form','form_id');
    }


}
