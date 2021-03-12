<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
   protected $table = 'parameters';
   protected $PrimaryKey = 'id';
   protected $fillable = ['section_id','name','idSuperior'];
    
   public function form(){
       return $this->belongsTo('App\Models\Form','id');
   }
}
