<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormAnswer extends Model
{
    protected $table = 'form_answers';
    protected $PrimaryKey = 'id';
    protected $fillable = ['section_id','structure_answer'];
}
