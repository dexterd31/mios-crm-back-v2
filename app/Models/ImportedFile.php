<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedFile extends Model
{
    protected $fillable = [
        'name'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    public function clients()
    {
        return $this->belongsToMany(ClientNew::class, 'imported_file_client');
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    public function scopeNameFilter($query, $name)
    {
        if ($name) {
            return $query->where('name', 'LIKE', "%$name%");
        }
    }
}
