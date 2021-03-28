<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tray extends Model
{
    protected $fillable = [
        'name','form_id','fields', 'state'
    ];
}
