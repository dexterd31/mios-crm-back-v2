<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use App\Models\User;

class TemplateController extends Controller
{
    private $templateModel;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function setTemplateModel($templateModel)
	{
		$this->templateModel = $templateModel;
	}

    public function getTemplateModel()
	{
		if($this->templateModel == null)
		{
			$this->setTemplateModel(new Template());
		}
		return $this->templateModel;
	}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $template)
    {
        $template = new Template([
            "form_id" => $template ->form_id,
            "user_rrhh_id" => $this->authUser()->rrhh_id,
            "template_name" => $template->template_name,
            "input_id" => json_encode($template->input_id),
            "fields_writable" => json_encode($template->fields_writable),
            "state" => 1,
            "value_delimiter" => $template->value_delimiter,
        ]);
        $template->save(); 
    }

    private function getiIputNames($template)
    {
        $sectionsNames = array();
        foreach ($template as $input)
        {
            array_push($sectionsNames, $input->nameInput);
        }
        return $sectionsNames;
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function show($formId)
    {
        $templateModel = $this->getTemplateModel();
        $template = $templateModel->select("id", "template_name", 'input_id')
            ->where("form_id", $formId)->get();
        $template->template_name = $this->getiIputNames($template->input_id);
        return $template;
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function delete($templateId)
    {
        $templateModel = $this->getTemplateModel();
        $templateModel->destroy($templateId);
    }

    public function buildTemplate(Request $request)
    {
        $formAnswer = $request->sections;
        $csv = '';
        $planilla = [];
        $templateModel = $this->getTemplateModel();
        $template = $templateModel->findOrFail($request->template_id);
        foreach($formAnswer as $section)
        {
            foreach($section['fields'] as $field)
            {
                $inputId = json_decode($template->input_id, true);
                if(array_key_exists($field['id'], $inputId))
                {
                    $fieldTemplate = $inputId[$field['id']];
                    array_push($planilla, $field);
                    $csv.= $fieldTemplate["registerDelimiter"];
                    if($fieldTemplate["haveTheFieldName"])
                    {
                        $csv .= $field["label"].":";
                    }
                    $csv .= $field["value"].$fieldTemplate["registerDelimiter"].$template->value_delimiter;
                }
            }
        }
        $csv = rtrim($csv, $templateModel->value_delimiter);
        $data = [];
        $data['csv'] = $csv;
        $data['planilla'] = $planilla;
        return $data;
    }
}
