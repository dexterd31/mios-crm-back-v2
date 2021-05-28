<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RolCrm;
use App\Models\PermissionCrm;

class RolCrmController extends Controller
{
    private $permissionCrmController;

    public function __construct(PermissionCrmController $permissionCrmController)
    {
        $this->permissionCrmController = $permissionCrmController;
    }
    public function createRolCrm(Request $request)
    {
        $rol = $request->roles;
        $rolCrm = new RolCrm([
            'ciu_id'=> $rol['id'],
            'name' => $rol['name'],
            'key' => $rol['key'],
            'status' => 1
        ]);

        $rolCrm->save();
        $this->permissionCrmController->createPermissionCrm($rol['menu_ids'], $rolCrm->id);
    }
}
