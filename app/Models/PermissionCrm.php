<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionCrm extends Model
{
    protected $table = 'permissions_crm';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'rol_id',
        'module_id',
        'save',
        'view',
        'edit',
        'change',
        'status'
    ];

    public function rol() {
        return $this->belongsTo('App\Models\RolCrm', 'rol_id');
    }

    public function module() {
        return $this->belongsTo('App\Models\ModuleCrm', 'module_id');
    }
}
