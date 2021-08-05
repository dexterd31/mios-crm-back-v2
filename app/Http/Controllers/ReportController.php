<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    private $reportModel;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function setReportModel($reportModel)
	{
		$this->reportModel = $reportModel;
	}

    public function getReportModel()
	{
		if($this->reportModel == null)
		{
			$this->setReportModel(new Report());
		}
		return $this->reportModel;
	}

    public function show($formId)
    {
        $reportModel = $this->getReportModel();
        return $reportModel->where("form_id", $formId)->get();
    }
}
