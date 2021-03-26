<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolCrm extends Model
{
    protected $table = 'roles_crm';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'ciu_id',
        'name',
        'key',
        'status'
    ];

    public function permission()
    {
        return $this->hasMany('App\Models\PermissionCrm', 'rol_id');
    }
}
