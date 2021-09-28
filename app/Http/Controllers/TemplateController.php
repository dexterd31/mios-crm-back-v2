<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use App\Models\User;
use Helpers\MiosHelper;
use Illuminate\Support\Facades\DB;

class TemplateController extends Controller
{
    private $templateModel;
    private $miosHelper;

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

    public function setMiosHelper($miosHelper)
	{
		$this->miosHelper = $miosHelper;
	}

    public function getMiosHelper()
	{
		if($this->miosHelper == null)
		{
			$this->setMiosHelper(new MiosHelper());
		}
		return $this->miosHelper;
	}

    /**
     * João Beleño
     * 02-08-2021
     * Método para crear el template
     */
    public function store(Request $template)
    {
        try {
            $template = new Template([
                "form_id" => $template ->form_id,
                "user_rrhh_id" => $this->authUser()->rrhh_id,
                "template_name" => $template->template_name,
                "input_id" => json_encode($template->input_id),
                "fields_writable" => json_encode($template->fields_writable),
                "state" => 1,
                "value_delimiter" => $template->value_delimiter,
                "template_html" => $template->template_html,
            ]);
            $template->save();
            $miosHelper = $this->getMiosHelper();
            return $miosHelper->jsonResponse(true, 200, 'message', 'Plantilla guardada correctamente');
        } catch (\Throwable $th)
        {
            $miosHelper = $this->getMiosHelper();
            return $miosHelper->jsonResponse(false, 500, 'message', 'Ha ocurrido un error');
        }
    }

    /**
     * João Beleño
     * 02-08-2021
     * Método para listar los nombres de los campos del template
     */
    private function getiInputNames($templates)
    {
        foreach($templates as $template)
        {
            $inputNames = array();
            $inputs = json_decode($template->input_id, true);
            foreach ($inputs as $input)
            {
                array_push($inputNames, $input['label']);
            }
            unset($template->input_id);
            $template->inputNames = $inputNames;
        }
        return $templates;
    }

    /**
     * João Beleño
     * 02-08-2021
     * Metodo para listar los templates
     */
    public function show(Request $request, $formId)
    {
        $paginate = $request->query('n', 5);
        $templateModel = $this->getTemplateModel();
        //filtrando por nombre
        if($fetch = $request->input('fetch'))
        {
            $templateModel = $templateModel->where("template_name", 'like', '%'.$fetch.'%');
        }

        $template = $templateModel->select("id", "template_name", 'input_id', 'created_at',
            DB::raw("(CASE WHEN state = '1' THEN 'Activado' ELSE 'Desactivado' END) AS state"))
            ->where("form_id", $formId)->paginate($paginate)->withQueryString();

        $template = $this->getiInputNames($template);
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
     * João Beleño
     * 02-08-2021
     * Método para borrar el template
     */
    public function delete($templateId)
    {
        $templateModel = $this->getTemplateModel();
        $templateModel->destroy($templateId);
    }

    /**
     * João Beleño
     * 02-08-2021
     * Metodo para crear el template con las respuestas y el csv
     */
    public function buildTemplate(Request $request)
    {
        $csv = [];
        $plantilla = array();
        $templateModel = $this->getTemplateModel();
        $template = $templateModel->findOrFail($request->template_id);
        $formAnswer = json_decode($request->sections, true);
        $valueDelimiter = is_numeric($template->value_delimiter)  ? chr($template->value_delimiter) : "";
        $inputsId = json_decode($template->input_id, true);
        foreach ($inputsId as $key => $inputId)
        {
            foreach($formAnswer as $section)
            {
                foreach($section['fields'] as $field)
                {
                    $csvValue='';
                    if((is_array($inputId["id"]) && in_array($field['id'], $inputId["id"])) || $inputId["id"] == $field['id'])
                    {
                        if($field["type"] == "options")
                        {
                            foreach ($field["options"] as $option)
                            {
                                if($option['id'] == $field["value"])
                                {
                                    $field["value"] = $option['name'];
                                }
                            }
                        }
                        $fieldTemplate = $inputId;
                        $registerDelimiter = is_numeric($fieldTemplate["registerDelimiter"]) ? chr($fieldTemplate["registerDelimiter"]) : "";
                        $csvValue.= $registerDelimiter;
                        if($fieldTemplate["haveTheLabel"])
                        {
                            $csvValue.= $field["label"].":";
                        }
                        $csvValue.= $field["value"].$registerDelimiter;
                        if(!isset($plantilla[$key]['value'])){
                            $plantilla[$key]= $field;
                            $csv[$key]=$csvValue;
                        }else if($plantilla[$key]['value']==''){
                            $plantilla[$key]= $field;
                            $csv[$key]=$csvValue;
                        }
                    }
                }
            }
        }
        $data = [];
        $data['csv'] = implode($valueDelimiter,$csv).$valueDelimiter;
        $data['plantilla'] = $plantilla;
        $data['fields_writable'] = $template->fields_writable;
        $data['value_delimiter'] = $template->value_delimiter;
        return $data;
    }

    /**
     * Metodo para generar plantilla html
     * @param Request id del la plantilla en  $request->templateId.
     * @param Request La tipificacion del formulario en $request->sections.
     * @return String Retorna una string que es el codigo html de la plantilla
     * @author Joao Alfonso Beleño
     * @exemple 
     * @createdate 28/09/2021
     */
    public function buildTemplateHtml(Request $request)
    {
        $template = Template::find($request->templateId);
        $templateHtml = $template->template_html;
        if(!$template || !$template->template_html)
        {
            //mensaje de error
            return "";
        }

        $formAnswer = json_decode($request->sections);
        $inputsId = json_decode($template->input_id);
        foreach ($formAnswer as $section)
        {
            foreach($section->fields as $field)
            {
                foreach ($inputsId as $inputId)
                {
                    if($inputId["id"] == $field['id'])
                    {
                        $templateHtml = str_replace("{{".$field->id."}}", $field->value, $templateHtml);
                    }
                }
            }
        }
        return json_encode($templateHtml);;
    }
}
