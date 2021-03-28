<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escalation extends Model
{
    protected $fillable = [
        'name','form_id', 'asunto_id', 'estado_id','fields', 'state'
    ];

    protected $casts = [
        'fields' => 'array',
    ];

}
