<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FormType;
use App\Models\Section;
use App\Models\Group;
use App\Models\StateForm;
use App\Models\Campaing;



class Form extends Model
{
    protected $table = 'forms';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id', 'campaign_id', 'form_type_id', 'name_form', 'key'];

    public function formtype(){
        return $this->hasOne(FormType::class, 'id', 'form_type_id');
    }

    public function section(){
        return $this->hasMany(Section::class, 'form_id');
    }

    public function group(){
        return $this->hasOne(Group::class,'group_id','id');
    }

    public function stateform(){
        return $this->belongsTo(StateForm::class,'form_id');
    }

    public function campaign(){
        return $this->hasOne(Campaing::class, 'campaign_id','id');
    }
    
}
