<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $PrimaryKey = 'id';
    protected $fillable = ['id_rhh','state'];

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_users', 'rrhh_id', 'group_id');
    }

    public function scopeRrhhFilter($query, $rrhh)
    {
        if ($rrhh) {
            return $query->where('id_rhh', $rrhh);
        }
    }
}
