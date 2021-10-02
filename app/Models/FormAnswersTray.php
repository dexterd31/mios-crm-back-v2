<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FormAnswer;
use App\Models\Tray;

class FormAnswersTray extends Model
{
    protected $table = 'form_answers_trays';
    protected $PrimaryKey = 'id';
    protected $fillable = ["id", "form_answer_id", "tray_id", "structure_answer_tray"];

    public function formAnswers(){
        return $this->belongsTo(FormAnswer::class, 'form_answer_id');
    }
    
    public function trays(){
        return $this->belongsTo(Tray::class, 'tray_id');
    }
}
