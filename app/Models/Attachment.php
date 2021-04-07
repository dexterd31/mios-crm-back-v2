<?php

namespace App\Models;

use App\Models\FormAnswer;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = ['form_answer_id','name','source'];

    public function formAnswer(){
        return $this->belongsTo(FormAnswer::class);
    }
}
