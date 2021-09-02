<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\form;
class Group extends Model
{
    protected $table = 'groups';
    protected $PrimaryKey = 'id';
    protected $fillable = ['campaign_id','name_group', 'description','state', "rrhh_id_creator"];

    public function campaign(){
        return $this->belongsTo('App\Models\Campaing', 'group_id');
    }
    
    public function forms(){
        return $this->hasMany(Form::class);
    }
    
    public function groupuser(){
        return $this->belongsTo('App\Models\GroupUser','group_id');

    }
}
