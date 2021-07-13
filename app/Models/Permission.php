<?php

namespace App\Models;

use App\Models\ActionPermission;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleCrm;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'role_ciu_id',
        'module_id',
        'action_permission_id'
    ];

    public function actionPermissions()
    {
        return $this->hasMany(ActionPermission::class, 'action_permission_id');
    }

    public function module()
    {
        return $this->belongsTo(ModuleCrm::class, 'module_id');
    }
}
