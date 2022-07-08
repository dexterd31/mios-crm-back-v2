<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientTag extends Model
{
    protected $table = 'client_tag';

    protected $fillable = [
        'client_new_id',
        'tag_id'
    ];

}
