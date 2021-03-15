<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
   protected $table = 'parameters';
   protected $PrimaryKey = 'id';
   protected $fillable = ['section_id','name','options','idSuperior'];
    
   public function section(){
       return $this->belongsTo('App\Models\Section','id');
   }
}
