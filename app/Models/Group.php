<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Form;

class Group extends Model
{
    protected $table = 'groups';
    protected $PrimaryKey = 'id';
    protected $fillable = ['campaign_id','name_group', 'description','state', "rrhh_id_creator"];

    /**
     * Joao Beleno
     * 02-09-2021
     * Relaciona tabla campaign
     * @deprecated: Se borra tabla de la base de datos
     */
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
