<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'token'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    public function forms()
    {
        return $this->belongsToMany(Form::class, 'form_product');
    }
}
