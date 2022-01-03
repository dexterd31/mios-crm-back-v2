<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClientNew;
use App\Models\Form;

class Directory extends Model
{
    protected $table = 'directories';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','rrhh_id', 'client_id','data', 'client_new_id','form_index'];

    public function form(){
       return $this->belongsTo(Form::class,'form_id');
    }

    public function client(){
        return $this->belongsTo('App\Models\Client', 'client_id');
    }

    public function clientNew()
    {
        return $this->belongsTo(ClientNew::class, 'client_new_id');
    }
}
