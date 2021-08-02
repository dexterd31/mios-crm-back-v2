<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;

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
        return $this->hasMany(Permission::class, 'module_id');
    }
}
