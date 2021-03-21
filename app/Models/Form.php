<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $table = 'forms';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id', 'campaign_id', 'form_type_id', 'name_form','filters','state'];

    public function formtype(){
        return $this->hasOne('App\Models\FormType', 'id');
    }

    public function section(){
        return $this->hasMany('App\Models\Section', 'form_id');
    }

    public function group(){
        return $this->hasOne('App\Models\Group','group_id','id');
    }

    public function stateform(){
        return $this->hasMany('App\Models\StateForm','form_id');
    }

    public function campaign(){
        return $this->hasOne('App\Models\Campaing', 'campaign_id','id');
    }
    
    public function formAnswer(){
        return $this->belongsTo('App\Models\FormAnswer','form_id');
    }
    public function keyvalue(){
        return $this->hasMany('App\Models\KeyValue','form_id');
    }
    public function upload(){
        return $this->hasMany('App\Models\Upload','form_id');
    }

    public function directory(){
        return $this->hasMany('App\Models\Upload','form_id');
    }
    
    public function apiConnection(){
        return $this->hasMany('App\Models\ApiConnection','form_id');
    }
}
