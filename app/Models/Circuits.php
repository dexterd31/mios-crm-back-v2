<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Circuits extends Model
{
    protected $table = 'circuits';
    protected $fillable = [
        'name',
        'key',
        'campaign_id',
        'state'
    ];
}
