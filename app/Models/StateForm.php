<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StateForm extends Model
{
    protected $table = 'state_forms';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','form_type_id','form_subtype_id','approval','observation','date_update'];

    public function form(){
        return $this->hasMany('App\Models\Form','id');
    }

    public function formtype(){
        return $this->hasMany('App\Models\FormType','id');
    }

    public function formsubtype(){
        return $this->hasMany('App\Models\FormSubType','id');
    }

}
