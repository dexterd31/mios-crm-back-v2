<?php

namespace App\Models;

use App\Models\Form;
use App\Models\FormAnswer;
use Illuminate\Database\Eloquent\Model;
use App\Models\RelTrayUser;

class Tray extends Model
{
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'name','form_id','fields','rols', 'state'
    ];

    public function form(){
        return $this->belongsTo(Form::class, "form_id");
    }

    public function formAnswers(){
        return $this->belongsToMany(FormAnswer::class, 'form_answers_trays');
    }

    public function RelTrayUser()
    {
        return $this->hasMany(RelTrayUser::class);
    }

    public function formAnswersTray(){
        return $this->hasMany(FormAnswersTray::class);
    }
}
