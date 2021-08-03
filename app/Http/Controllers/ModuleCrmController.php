<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModuleCrm;

class ModuleCrmController extends Controller
{
    private $moduleCrmModel;

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

    public function store(Request $request)
    {
        $moduleCrmModel = $this->getModuleCrmModel();
        return $moduleCrmModel->select("id", "label")->get();
    }
}
