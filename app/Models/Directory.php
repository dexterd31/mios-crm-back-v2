<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Directory extends Model
{
    protected $table = 'directories';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','user_id', 'client_id','data'];

    public function form(){
       return $this->belongsTo('App\Models\Form','form_id');
    }

    public function client(){
        return $this->belongsTo('App\Models\Client', 'client_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }

}
