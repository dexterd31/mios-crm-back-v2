<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use stdClass;
use App\Models\ModuleCrm;

class PermissionController extends Controller
{
    private $permissionModel;
    private $moduleCrmModel;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function setPermissionModel($permissionModel)
	{
		$this->permissionModel = $permissionModel;
	}

    public function getPermissionModel()
	{
		if($this->permissionModel == null)
		{
			$this->setPermissionModel(new Permission());
		}
		return $this->permissionModel;
	}

    public function setModuleCrmModel($moduleCrmModel)
	{
		$this->moduleCrmModel = $moduleCrmModel;
	}

    public function getModuleCrmModel()
	{
		if($this->moduleCrmModel == null)
		{
			$this->setModuleCrmModel(new ModuleCrm());
		}
		return $this->moduleCrmModel;
	}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $idRolesCrm = $this->authUser()->rolesId[0]->crm;
        $permissionModel = $this->getPermissionModel();
        $idRole = end($idRolesCrm);
        $permissions = $permissionModel->where('role_ciu_id', $idRole)->get();
        $pemit = $this->gatPemitDefault();
        foreach($permissions as $permission)
        {
            $action = $permission->actionPermissions->action;
            $pemit[$permission->module_id - 1]->$action = 1;
        }
        $rolePermission["rol_name"] = "";
        $rolePermission["rol_id"] = $idRole;
        $rolePermission["modules"] = $pemit;
        return ["permissions" => $rolePermission];
    }

    private function gatPemitDefault()
    {
        $permissionDefault = [];
        $moduleCrmModel = $this->getModuleCrmModel();
        $modulesCrm = $moduleCrmModel->all();
        foreach ($modulesCrm as $moduleCrm)
        {
            $permissionDefault[$moduleCrm->id - 1] = (object)[
                'save' => 0,
                'view' => 0,
                'edit' => 0,
                'change' => 0,
                'all' => 0,
            ];
        }
        return $permissionDefault;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $permissions = array();
        foreach ($request->permissions as $permissionData)
        {
            $permissionData = json_decode($permissionData);
            $permission = [
                'role_ciu_id' => $request->idRole,
                'module_id' => $permissionData->module,
                'action_permission_id' => $permissionData->action
            ];
            array_push($permissions, $permission);
        }
        $permissionModel = $this->getPermissionModel();
        $permissionModel->insert($permissions);  
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $permissionModel = $this->getPermissionModel();
        $permissionModel->where('role_ciu_id', $request->idRole)->delete();
        $this->create($request);
    }


    // "permissions": {
    //     "crm": {"typify_form_record": ["save","view","edit","change"]}
    //   },

    public function getPermissions()
    {
        $idRoles = $this->authUser()->rolesId[0]->crm;
        $permissionModel = $this->getPermissionModel();
        $permissionsData = $permissionModel->whereIn('role_ciu_id', $idRoles)->with("module")->get();
        $permissions = [];
        foreach ($permissionsData as $permissionData)
        {
            $action = $permissionData->actionPermissions->action;
            $actionId = $permissionData->actionPermissions->id;
            $moduleName = $permissionData->module->name;

            if(!array_key_exists($moduleName, $permissions))
            {
                $permissions[$moduleName] = (Object)[];
            }
            if(!isset($permissions[$moduleName]->$action))
            {
                $permissions[$moduleName]->$action = $actionId;
            }
        }
        return $permissions;
    }

    public function getPermissionsByIdRole($idRole)
    {
        $permissionModel = $this->getPermissionModel();
        return $permissionModel->select("module_id","action_permission_id")
            ->where('role_ciu_id', $idRole)->get();
    }
}
