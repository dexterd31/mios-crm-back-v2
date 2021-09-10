<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormLog extends Model
{
    protected $table = 'form_logs';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id', 'campaign_id', 'form_id', 'name_form','filters','state', 'sections', 'rrhh_id'];

    public function form(){
        return $this->belongsTo('App\Models\Form', 'form_id');
    }
}
