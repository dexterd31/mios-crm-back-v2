<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClientNew;

class Directory extends Model
{
    protected $table = 'directories';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','rrhh_id', 'client_id','data', 'client_new_id'];

    public function form(){
       return $this->belongsTo('App\Models\Form','form_id');
    }

    public function client(){
        return $this->belongsTo('App\Models\Client', 'client_id');
    }

    public function clientNew()
    {
        return $this->belongsTo(ClientNew::class, 'client_new_id');
    }
}
