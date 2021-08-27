<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Directorie;
 

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
}
