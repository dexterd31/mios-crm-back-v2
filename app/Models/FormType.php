<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormType extends Model
{
    protected $table = 'formtypes';
    protected $PrimaryKey = 'id';
    protected $fillable = ['name_type', 'description', 'key'];

    public function form(){
        return $this->hasMany('App\Models\Form', 'form_type_id');
    }
    public function stateform(){
        return $this->belongsTo('App\Models\StateForm', 'form_type_id');
    }
}
