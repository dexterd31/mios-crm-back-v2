<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiConnection extends Model
{
    protected $table = 'api_connections';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'name',
        'form_id',
        'url',
        'autorization_type',
        'token',
        'other_autorization_type',
        'other_token',
        'mode',
        'parameter',
        'json_send',
        'json_response',
        'request_type',
        'status',
        'api_type'
    ];

    public function form()
    {
        return $this->belongsTo('App\Models\Form', 'form_id');
    }

    public function apiQuestion(){
        return $this->hasMany('App\Models\ApiQuestion','api_id');
    }
}
