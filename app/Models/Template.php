<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Form;

class Template extends Model
{
    protected $table = 'templates';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        "form_id",
        "user_rrhh_id",
        "template_name",
        "input_id",
        "fields_writable",
        "fields_name",
        "state",
        "value_delimiter"
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, "form_id");
    }
}
