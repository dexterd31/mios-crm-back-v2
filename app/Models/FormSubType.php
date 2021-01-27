<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubType extends Model
{
    protected $table = 'form_sub_types';
    protected $PrimaryKey = 'id';
    protected $fillable = ['name', 'description', 'key'];

    public function FormType(){
        return $this->BelongsTo('App\Models\FormType', 'formsubtype_id');
    }
}
