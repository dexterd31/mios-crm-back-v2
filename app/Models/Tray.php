<?php

namespace App\Models;

use App\Models\Form;
use App\Models\FormAnswer;
use Illuminate\Database\Eloquent\Model;

class Tray extends Model
{
    protected $fillable = [
        'name','form_id','fields','rols', 'state'
    ];

    public function form(){
        return $this->belongsTo(Form::class);
    }

    public function formAnswer(){
        return $this->hasMany(FormAnswer::class);
    }
}
