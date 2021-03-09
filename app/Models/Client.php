<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';
    protected $PrimaryKey = 'id';
    protected $fillable = ['document_type_id','first_name','middle_name','first_lastname','second_lastname','document','phone','email'];

    
    public function formanswer(){
        return $this->hasMany('App\Models\FormAnswer', 'client_id');
    }

    public function keyvalue(){
        return $this->belongsTo('App\Models\KeyValue','client_id');
    }

    public function documenttype(){
        return $this->HasOne('App\Models\DocumentType','id','document_type_id');
    }
}
