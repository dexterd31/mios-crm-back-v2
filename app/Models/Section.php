<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = 'sections';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id', 'name_section', 'type_section','fields'];

    public function Form(){
        return $this->belongsTo('App\Models\Form', 'id');
    }
    
    public function parameter(){
        return $this->hasMany('App\Models\Parameter','form_id');
    }
   
}
