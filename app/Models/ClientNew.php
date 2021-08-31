<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Directorie;
use App\Models\FormAnswer;
use App\Models\KeyValue;


class ClientNew extends Model
{
    protected $table = 'client_news';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        "form_id",
        "information_data",
        "unique_indentificator",
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function directorie()
    {
        return $this->hasOne(Directorie::class);
    }

    public function keyValues()
    {
        return $this->hasMany(KeyValue::class);
    }

    public function formanswer(){
        return $this->hasMany(FormAnswer::class, 'client_id');
    }
}
