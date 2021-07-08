<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RolCrm;
use App\Models\PermissionCrm;
use Log;

class RolCrmController extends Controller
{
    private $permissionCrmController;

    public function __construct()
    {
        $this->middleware('auth');
    }

    //Metdos set y get para implementar pruebas unitarias
    public function setPermissionCrmController($permissionCrmController)
	{
		$this->permissionCrmController = $permissionCrmController;
	}

    public function getPermissionCrmController()
	{
		if($this->permissionCrmController == null)
		{
			$this->setPermissionCrmController(new PermissionCrmController());
		}
		return $this->permissionCrmController;
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

        //llamando metodo save atraves del Controller para implementar pruebas unitarias
        $rolCrm = $this->saveModel($rolCrm);

        if(array_key_exists('menu_ids', $rol) && $rol['menu_ids'])
        {
            $permissionCrmController = $this->getPermissionCrmController();
            $permissionCrmController->createPermissionCrm($rol['menu_ids'], $rolCrm->id);
        }
    }
}
