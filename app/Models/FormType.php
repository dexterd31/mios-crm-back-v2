<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormType extends Model
{
    protected $table = 'formtypes';
    protected $PrimaryKey = 'id';
    protected $fillable = ['formsubtype_id', 'name_type', 'description', 'key'];

    public function formsubtype(){
        return $this->hasMany('App\Models\FormSubType', 'id');
    }
    public function form(){
        return $this->hasOne('App\Models\Form', 'form_type_id');
    }
}
