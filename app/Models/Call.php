<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $table = 'calls';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id', 'phone_number', 'duration','status','observation'];

    public function form(){
        return $this->belongsTo('App\Models\Form', 'id');
    }
}
