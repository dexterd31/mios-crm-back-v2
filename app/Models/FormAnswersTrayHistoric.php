<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormAnswersTrayHistoric extends Model
{
    protected $table = 'form_answer_trays_historic';
    protected $PrimaryKey = 'id';
    protected $fillable = ["id", "form_answers_trays_id", "structure_answer"];

    public function formAnswersTrays(){
        return $this->belongsTo(formAnswersTrays::class, 'form_answers_trays_id');
    }

}
