<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormAnswersTray extends Model
{ 
    protected $table = 'form_answers_trays';
    protected $fillable = ["form_answer_id", "tray_id"];

    public function formAnswers(){
        return $this->belongsTo(FormAnswer::class, 'form_answer_id');
    }
    
    public function trays(){
        return $this->belongsTo(Tray::class, 'tray_id');
    }
}
