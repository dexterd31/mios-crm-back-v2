<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RolCrm;
use App\Models\PermissionCrm;

class RolCrmController extends Controller
{
    private $permissionCrm;

    public function __construct(PermissionCrm $permissionCrm)
    {
        $this->permissionCrm = $permissionCrm;
    }
    public function createRolCrm(Request $request)
    {
        $rol = $request->roles;
        $rolCrm = new RolCrm([
            'ciu_id'=> $rol['ciu_id'],
            'name' => $rol['name'],
            'key' => $rol['key'],
            'status' => 1
        ]);
        $rolCrm->save();
        $this->permissionCrm->createPermissionCrm($rol['modulos'], $rolCrm->id);
    }
}
