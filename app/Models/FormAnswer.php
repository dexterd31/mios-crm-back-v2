<?php

namespace App\Models;

use App\Models\Attachment;
use App\Models\Tray;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClientNew;

class FormAnswer extends Model
{
    protected $table = 'form_answers';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','rrhh_id', 'client_id','channel_id','structure_answer', "client_new_id"];

    public function form(){
       return $this->hasMany('App\Models\Form','id');
    }

    public function client(){
        return $this->belongsTo('App\Models\Client', 'client_id');
    }

    public function ClientNew(){
        return $this->belongsTo(ClientNew::class, 'client_new_id');
    }

    public function channel(){
        return $this->hasMany('App\Models\Channel', 'id');
    }

    public function atachments(){
        return $this->hasMany(Attachment::class);
    }

    public function trays(){
        return $this->belongsToMany(Tray::class, 'form_answers_trays');
    }
}
