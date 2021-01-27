<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = 'sections';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id', 'name', 'fields'];

    public function Form(){
        return $this->HasOne('App\Models\Form', 'id');
    }
}
