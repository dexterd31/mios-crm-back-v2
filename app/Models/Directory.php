<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Directory extends Model
{
    protected $table = 'directories';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','rrhh_id', 'client_id','data'];

    public function form(){
       return $this->belongsTo('App\Models\Form','form_id');
    }

    public function client(){
        return $this->belongsTo('App\Models\Client', 'client_id');
    }
}
