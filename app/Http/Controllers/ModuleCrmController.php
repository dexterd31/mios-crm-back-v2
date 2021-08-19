<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModuleCrm;
use App\Models\ActionPermission;

class ModuleCrmController extends Controller
{
    private $moduleCrmModel;
    private $actionPermissionModel;

    public function __construct()
    {
        $this->middleware('auth');
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

    public function setActionPermissionModel($actionPermissionModel)
	{
		$this->actionPermissionModel = $actionPermissionModel;
	}

    public function getActionPermissionModel()
	{
		if($this->actionPermissionModel == null)
		{
			$this->setActionPermissionModel(new ActionPermission());
		}
		return $this->actionPermissionModel;
	}

    public function store()
    {
        $moduleCrmModel = $this->getModuleCrmModel();
        $actionPermissionModel = $this->getActionPermissionModel();
        return [
            "modules" => $moduleCrmModel->select("id", "label")->get(),
            "action_permission" => $actionPermissionModel->select("id", "name", "action")->get()
        ];
    }
}
