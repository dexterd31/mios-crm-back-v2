<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    //? Filters --------------------------------------------------------------------------------------------------------

    public function scopeNameFilter($query, $name)
    {
        if ($name) return $query->where('name_channel', 'LIKE', "%$name%");
    }
}
