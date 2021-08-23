<?php

namespace App\Models;

use App\Models\Attachment;
use App\Models\Tray;
use Illuminate\Database\Eloquent\Model;

class FormAnswerLog extends Model
{
    protected $table = 'form_answer_logs';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_answer_id','structure_answer', 'rrhh_id'];

    public function form(){
       return $this->belongsTo('App\Models\FormAnswer','id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','id');
    }

}
