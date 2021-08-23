<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $PrimaryKey = 'id';
    protected $fillable = ['id_rhh','state'];
}
