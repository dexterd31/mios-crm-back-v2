<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $table = 'forms';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id', 'campaign_id', 'form_type_id', 'name_form', 'key'];

    public function formtype(){
        return $this->hasOne('App\Models\FormType', 'id', 'form_type_id');
    }

    public function section(){
        return $this->hasMany('App\Models\Section', 'form_id');
    }

    public function group(){
        return $this->hasOne('App\Models\Group','group_id','id');
    }

    public function stateform(){
        return $this->belongsTo('App\Models\StateForm','form_id');
    }

    public function campaign(){
        return $this->hasOne('App\Models\Campaing', 'campaign_id','id');
    }
    
}
