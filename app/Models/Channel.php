<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    public function scopeNameFilter($query, $name)
    {
        if ($name) {
            return $query->where('name_channel', $name);
        }
    }
}
