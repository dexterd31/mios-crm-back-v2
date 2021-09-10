<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClientNew;

class KeyValue extends Model
{
    protected $table = 'key_values';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','client_id','key','value','description', 'field_id', 'client_new_id'];

    public function client(){
        return $this->hasMany('App\Models\Client','id');
    }
    
    public function form(){
        return $this->belongsTo('App\Models\Form','id');
    }

    public function clientNew()
    {
        return $this->belongsTo(ClientNew::class, 'client_new_id');
    }
}
