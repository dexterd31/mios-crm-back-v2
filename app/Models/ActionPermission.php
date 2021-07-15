<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;
class ActionPermission extends Model
{
    protected $table = 'action_permissions';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        "action",
        "name"
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
