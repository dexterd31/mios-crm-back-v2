<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use stdClass;

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
    public function index($rolCiuId)
    {
        $idRoles = $this->authUser()->rolesId;
        $permissionModel = $this->getPermissionModel();
        $idRole = end($idRoles);
        $permissions = $permissionModel->where('role_ciu_id', $idRole)->get();
        return $permissions->module;
        $rolePermission = ['RoleId' => $rolCiuId];
        foreach($permissions as $permission)
        {
            if(!array_key_exists($permission->module_id, $rolePermission))
            {
                $rolePermission[$permission->module_id] = [];
            }
            array_push($rolePermission[$permission->module_id], $permission->actionPermissions->action);
        }
        return $rolePermission;
    }

    private function createPemitDefault()
    {
        $permissionDefault = [];
        $moduleCrmModel = $this->getModuleCrmModel();
        $modulesCrm = $moduleCrmModel->all();
        foreach ($modulesCrm as $moduleCrm)
        {
            $permissionDefault[$moduleCrm->id] = new stdClass();
            $permissionDefault[$moduleCrm->id - 1]->save = 0;
            $permissionDefault[$moduleCrm->id - 1]->view = 0;
            $permissionDefault[$moduleCrm->id - 1]->edit = 0;
            $permissionDefault[$moduleCrm->id - 1]->change = 0;
            $permissionDefault[$moduleCrm->id - 1]->status = 0;
            $permissionDefault[$moduleCrm->id - 1]->all = 0;
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
        foreach ($request->roles as $role)
        {
            foreach ($role['actions_permission_id'] as $action_permission_id)
            {
                $permission = [
                    'role_ciu_id' => $request->idRole,
                    'module_id' => $role['module_id'],
                    'action_permission_id' => $action_permission_id
                ];
                array_push($permissions, $permission);
            }
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
}
