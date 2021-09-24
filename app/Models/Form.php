<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Template;
use App\Models\Report;
use App\Models\Group;
use App\Models\Section;
use App\Models\FormAnswer;
use App\Models\Tray;
use App\Models\Directory;

class Form extends Model
{
    protected $table = 'forms';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id', 'form_type_id', 'name_form','filters','state', 'seeRoles', 'fields_client_unique_identificator'];

    public function formtype(){
        return $this->belongsTo('App\Models\FormType', 'form_type_id');
    }

    public function trays()
    {
        return $this->hasMany(Tray::class);
    }

    public function section()
    {
        return $this->hasMany(Section::class);
    }

    public function group(){
        return $this->hasOne(Group::class,'group_id');
    }

    public function stateform(){
        return $this->hasMany('App\Models\StateForm','form_id');
    }

    public function formAnswers(){
        return $this->hasMany(FormAnswer::class);
    }

    public function keyvalue(){
        return $this->hasMany('App\Models\KeyValue','form_id');
    }
    public function upload(){
        return $this->hasMany('App\Models\Upload','form_id');
    }

    public function directory(){
        return $this->hasMany(Directory::class);
    }

    public function apiConnection(){
        return $this->hasMany('App\Models\ApiConnection','form_id');
    }

    public function apiQuestion(){
        return $this->hasMany('App\Models\ApiQuestion','api_id');
    }

    public function template()
    {
        return $this->hasMany(Template::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'report_id');
    }
}
