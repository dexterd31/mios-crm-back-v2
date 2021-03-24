<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleCrm extends Model
{
    protected $table = 'modules_crm';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'name',
        'status'
    ];

    public function permission()
    {
        return $this->hasMany('App\Models\PermissionCrm', 'module_id');
    }
}
