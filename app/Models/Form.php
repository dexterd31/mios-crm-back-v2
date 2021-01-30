<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $table = 'forms';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_type_id', 'name_form', 'description','key'];

    public function FormType(){
        return $this->hasOne('App\Models\FormType', 'id');
    }

    public function Section(){
        return $this->hasMany('App\Models\Section', 'form_id');
    }

    public function Call(){
        return $this->hasMany('App\Models\Call', 'form_id');
    }
}
