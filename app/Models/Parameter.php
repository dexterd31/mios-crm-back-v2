<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
   protected $table = 'parameters';
   protected $PrimaryKey = 'id';
   protected $fillable = ['form_id','name','options','idSuperior','dependency','have_dependencies','key'];
    
   public function form(){
       return $this->belongsTo('App\Models\Form','id');
   }
}
