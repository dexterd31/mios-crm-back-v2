<?php

namespace App\Http\Controllers;

use App\Exports\FormReportExport;
use App\Jobs\DeleteReport;
use App\Jobs\FormReport;
use App\Managers\ReportManager;
use App\Models\ApiConnection;
use App\Models\CustomerDataPreload;
use App\Models\Form;
use App\Models\FormLog;
use App\Models\FormType;
use App\Models\GroupUser;
use App\Models\RelAdvisorClientNew;
use App\Models\Section;
use App\Models\Tray;
use App\Models\User;
use App\Services\RrhhService;
use App\Traits\deletedFieldChecker;
use App\Traits\FindAndFormatValues;
use Helpers\MiosHelper;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use stdClass;


class FormController extends Controller
{
    use deletedFieldChecker, FindAndFormatValues;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Nicol Ramirez
     * 27-01-2020
     * Método para consultar los formularios existentes en la BD
     */
    public function FormsList(Request $request)
    {
        $rrhhid = auth()->user()->rrhh_id;
        $roles = auth()->user()->roles;
        $rolesArray = [];
        foreach ($roles as $value) {
            if (str_contains($value, 'crm::')) {
                $rolesArray[] = str_replace('crm::', '', $value);
            }
        }
        $paginate = $request->query('n', 5);
        $forms = $this->getFormsByIdUser($rrhhid, $paginate);

        foreach ($forms as $value) {
            if (count(array_intersect($rolesArray, json_decode($value->seeRoles))) > 0) {
                $value->roles = true;
            } else {
                $value->roles = false;
            }
        }
        return $forms;
    }

    /**
     * Método para consultar el formulario con sus respectivas secciones
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co> 
     *
     * @param mixed $id
     * @return void
     */
    public function searchForm($id)
    {
        $formsSections = Form::where('id', $id)
            ->with(["section" => function($q){
                $q->where('state', '!=', 1);
            }])
            ->select('*')
            ->first();

        $formsSections->seeRoles = json_decode($formsSections->seeRoles);
        $formsSections->filters = json_decode($formsSections->filters);
        for ($i = 0; $i < count($formsSections->section); $i++) {
            unset($formsSections->section[$i]['created_at']);
            unset($formsSections->section[$i]['updated_at']);
            $formId = $formsSections->section[$i]['form_id'];
            $fields = json_decode($formsSections->section[$i]['fields']);

            $formsSections->section[$i]['fields'] = collect($fields)->filter(function ($field) use ($formId){
                return !$this->deletedFieldChecker($formId, $field->id);
            });

            unset($formId, $fields);
            unset($formsSections->section[$i]['form_id']);
        }
        $formsSections->client_unique = json_decode($formsSections->fields_client_unique_identificator);
        $formsSections->campaign_id = auth()->user()->rrhh->campaign_id;
        /**
         * Se agrega validacion de api_connections para integracion con SBS (DataCRM)
         */
        $formsSections->externalNotifications = false;
        $apiConnection = ApiConnection::where('form_id',$id)->where('api_type',10)->where('status',1)->first();
        if($apiConnection) $formsSections->externalNotifications = true;
        $templateController = new TemplateController();
        $templateExist = (count($templateController->showByFormId($id)) > 0);
        $formsSections->template = $templateExist;
        $formsSections->view_chronometer = (boolean)$formsSections->tipification_time;
        $formsSections->count_assigned_clients = CustomerDataPreload::adviserFilter(auth()->user()->rrhh_id)
        ->formFilter($id)->managedFilter(false)->get(['id'])->count();
        $formsSections->count_assigned_clients += RelAdvisorClientNew::rrhhFilter(auth()->user()->rrhh_id)
        ->join('client_news', 'client_news.id', 'rel_advisor_client_new.client_new_id')
        ->where('client_news.form_id', $id)->managedFilter(false)->get(['rel_advisor_client_new.id'])->count();
        unset($formsSections->tipification_time);
        return response()->json($formsSections);
    }

    /**
     * Nicol Ramirez
     * 27-01-2020
     * Método para crear el formulario y sus secciones
     */
    public function saveForm(Request $request, MiosHelper $miosHelper)
    {
        //  try
        // {
            $unique_client=$request->client_unique;
            $filters_form=$request->filters;
            $filters_form_new=[];
            $forms = new Form([
                'group_id' =>  $request->input('group_id'),
                'form_type_id' => $request->input('type_form'),
                'name_form' => $request->input('name_form'),
                'filters' => json_encode($request->filters),
                'state' => $request->state,
                'seeRoles' => json_encode($request->role),
            ]);
            $forms->save();
           foreach($request['sections'] as $section)
           {
                for($i=0; $i<count($section['fields']); $i++){
                    $cadena = (string)$i;
                    if($section['fields'][$i]['key'] == 'null'){
                        //Reemplaza todos los acentos o tildes de la cadena
                        $section['fields'][$i]['key'] = $miosHelper->replaceAccents($section['fields'][$i]['label']);
                        //Reemplaza todos los caracteres extraños
                        $section['fields'][$i]['key'] = preg_replace('([^A-Za-z0-9 ])', '',$section['fields'][$i]['key']);
                        //Convertimos a minusculas y Remplazamos espacios por el simbolo -
                        $section['fields'][$i]['key'] = strtolower( str_replace(array(' ','  '),'-',$section['fields'][$i]['key']) );
                        //Concatenamos el resultado del label transformado con la variable $cadena
                        $section['fields'][$i]['key'] = $section['fields'][$i]['key'].$cadena;
                        foreach($unique_client as $key=>$uniqueField){
                            if($section['fields'][$i]['id'] == $uniqueField['id']){
                                $unique_client[$key]['key']=$section['fields'][$i]['key'];
                                $unique_client[$key]['client_unique']=true;
                                $section['fields'][$i]['client_unique']=true;
                                $section['fields'][$i]['preloaded']=true;
                                $section['fields'][$i]['required']=true;
                                $section['fields'][$i]['isClientInfo']=true;
                            }
                        }
                        if($section['fields'][$i]['value']=='Invalid date' && $section['fields'][$i]['controlType']=='datepicker'){
                            $section['fields'][$i]['value']="";
                        }
                        foreach($filters_form as $filter){
                            if($section['fields'][$i]['id'] == $filter['id']){
                                array_push($filters_form_new,$section['fields'][$i]);
                            }
                        }
                    }
                }
                $sections = new Section([
                    'id' => $section['idsection'],
                    'form_id' => $forms->id,
                    'name_section' => $section['sectionName'],
                    'type_section' => $section['type_section'],
                    'fields' => json_encode($section['fields']),
                    'collapse' => empty($section['collapse'])? 0 : $section['collapse'],
                    'duplicate' => empty($section['duplicar'])? 0 : $section['duplicar']
                ]);
                $sections->save();
            }
            $forms->filters = json_encode($filters_form_new);
            $forms->fields_client_unique_identificator = json_encode($unique_client);
            $forms->update();
            $data = ['code' => 200,'message'=>'Formulario Guardado Correctamente'];
            $this->logForm($forms, $request['sections']);
            return response()->json($data, $data['code']);

        //   }catch(\Throwable $e){
        //     return $this->errorResponse('Error al guardar el formulario',500);
        // }
    }


    /**
     * Nicoll Ramirez
     * 04-02-2021
     * Método para consultar el tipo de formulario en el select de creación de formulario
     */

    public function searchFormType()
    {
        $formtype = FormType::select('id', 'name_type')->get();
        return $formtype;
    }
    /**
     *Nicoll Ramirez
     *23-02-2021
     *Método para editar el formulario
     */


    public function editForm(Request $request, $id, MiosHelper $miosHelper)
    {
        // try {
            $form = Form::find($id);
            $form->group_id = $request->group_id;
            $form->form_type_id = $request->type_form;
            $form->name_form = $request->name_form;
            $form->filters = json_encode($request->filters);
            $form->seeRoles = json_encode($request->role);
            $form->save();
            $sectionNames = array();
            foreach($request->sections as $section)
            {
                $sectionNames[] = $section['sectionName'];
                for($i=0; $i<count($section['fields']); $i++){
                    $cadena = (string)$i;
                    if($section['fields'][$i]['key'] == 'null'){
                        $section['fields'][$i]['key'] = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'],$section['fields'][$i]['label']);
                       $section['fields'][$i]['key'] =  strtolower( str_replace(' ','-',$section['fields'][$i]['label']) );
                       $section['fields'][$i]['key'] = $section['fields'][$i]['key'].$cadena;
                    }
                    if($section['fields'][$i]['value']=='Invalid date' && $section['fields'][$i]['controlType']=='datepicker'){
                        $section['fields'][$i]['value']="";
                    }
                }
                if($section['sectionName'] == 'Datos básicos de cliente'){
                    $sections = Section::find($section['idsection']);
                    $sections->name_section = $section['sectionName'];
                    $sections->type_section = $section['type_section'];
                    $sections->fields = json_encode($section['fields']);
                    $sections->collapse = empty($section['collapse'])? 0 : $section['collapse'];
                    $sections->duplicate = empty($section['duplicar'])? 0 : $section['duplicar'];
                    $sections->save();
                } else {
                    $fields = $section['fields'];
                    $sections = Section::find($section['idsection']);

                    if($sections == null){
                        $sections = new Section([
                            'id' => $section['idsection'],
                            'form_id' => $form->id,
                            'name_section' => $section['sectionName'],
                            'type_section' => $section['type_section'],
                            'fields' => json_encode($fields),
                            'collapse' => empty($section['collapse'])? 0 : $section['collapse'],
                            'duplicate' => empty($section['duplicar'])? 0 : $section['duplicar']
                        ]);
                        $sections->save();
                    }else{
                        $sections->name_section = $section['sectionName'];
                        $sections->type_section = $section['type_section'];
                        $sections->fields = json_encode($fields);
                        $sections->collapse = empty($section['collapse'])? 0 : $section['collapse'];
                        $sections->duplicate = empty($section['duplicar'])? 0 : $section['duplicar'];
                        $sections->save();
                    }
                }
            }

            //jbernal-inactiva sections que no lleguem del formulario
            $sectionState = Section::where('form_id',$id)->whereNotIn('name_section', $sectionNames)->get();
            foreach ($sectionState as $state) {
                $state->state = 1;
                $state->save();
            }
            $data = ['forms' => $form, 'sections' => json_decode($sections->fields), 'code' => 200, 'message' => 'Formulario editado Correctamente'];

            $this->logForm($form, $request->sections);

            return response()->json($data, $data['code']);
        // } catch (\Throwable $e) {
        //     return $this->errorResponse('Error al editar el formulario', 500);
        // }
    }

    /**
     * Nicoll Ramirez
     * 23-02-2021
     * Método para desactivar el estado del formulario
     */
    public function deleteForm(Request $request, $id)
    {
        // try {
            $form = Form::find($id);
            $form->state = $request->state;
            $form->save();

            return $this->successResponse('Formulario desactivado correctamente');
        // } catch (\Throwable $e) {
        //     return $this->errorResponse('Error al desactivar el formulario', 500);
        // }
    }

    /**
     * @author: Leonardo Giraldo
     * Se cambia la funcion reportes evalua primero los campos que se deben reportar y despues compara con las respuestas
     */
    public function report(Request $request)
    {
        ini_set('memory_limit', '1000M');
        set_time_limit(0);

        dispatch(new FormReport($request->all(), auth()->user()->rrhh_id))->onQueue('form-report');

        return response()->json(['success' => 'Tu reporte se está generando... te notificaremos cuando esté disponible.']);
    }

    public function downloadReport($filename)
    {
        try{
            dispatch((new DeleteReport($filename))->delay(Carbon::now('America/Bogota')->addHour()))->onQueue('delete-report');
            return response()->download(storage_path("app/reports/$filename.xlsx"));
        }catch(Exception $ex){
            Log::info("Ha ocurrido un error al consultar el archivo: " . $ex->getMessage());
            return $this->errorResponse('Ocurrio un error al intentar descargar el archivo', 500);
        }
    }

    /**
     * Olme Marin
     * 25-03-2021
     * Método para consultar el listado de los formularios asignados a un usuario por grupo
     * @deprecated: La función FormList ya realiza la busqueda por usuarios y grupos Reportada 2021-06-10
     */
    public function formsByUser(MiosHelper $miosHelper, $rrhhId, Request $request)
    {
        $paginate = $request->query('n', 5);
        $forms = $this->getFormsByIdUser($rrhhId, $paginate);
        foreach ($forms as $form) {
            $form->filters = $miosHelper->jsonDecodeResponse($form->filters);
        }
        $data = $miosHelper->jsonResponse(true, 200, 'forms', $forms);
        return response()->json($data, $data['code']);
    }

    private function logForm($form, $sections)
    {
        $log = new FormLog();
        $log->group_id = $form->group_id ;
        $log->name_form = $form->name_form ;
        $log->filters = $form->filters ;
        $log->state = $form->state ;
        $log->sections = json_encode($sections) ;
        $log->rrhh_id = auth()->user()->rrhh_id;
        $log->form_id = $form->id;
        $log->save();
    }

    public function searchPrechargeFields($id)
    {
        $formsSections = Form::where('id', $id)
            ->with('section')
            ->select('*')
            ->first();
        $formsSections->seeRoles = json_decode($formsSections->seeRoles);
        $formsSections->filters = json_decode($formsSections->filters);
        for ($i = 0; $i < count($formsSections->section); $i++) {
            unset($formsSections->section[$i]['created_at']);
            unset($formsSections->section[$i]['updated_at']);
            unset($formsSections->section[$i]['form_id']);
            // $formsSections->section[$i]['fields'] = json_decode($formsSections->section[$i]['fields']);
            $fields = collect(json_decode($formsSections->section[$i]['fields']));

            if($i==0){
                for($j=0;$j<count($fields);$j++){
                    if($fields[$j]->preloaded == false){
                        unset($fields[$j]);
                    }
                }
            }else{
                $fields = $fields->filter(function($x){
                            return $x->preloaded == true;
                          });
            }
            $formsSections->section[$i]['fields'] = array_values($fields->toArray());
        }

        return response()->json($formsSections);
    }

    private function getFormsByIdUser($rrhhId, $paginate)
    {
        $forms = Form::join('form_types', 'forms.form_type_id', '=', 'form_types.id')
            ->join("groups", "groups.id", "forms.group_id")
            ->join('group_users', 'group_users.group_id', 'groups.id')
            ->select('name_form', 'forms.id', 'name_type', 'forms.state', 'seeRoles', 'forms.updated_at')
            ->where('group_users.rrhh_id', $rrhhId)
            ->paginate($paginate)->withQueryString();
        return $forms;
    }

    /**
     * @author: Daniel Martinez
     * Función para duplicar una sección
     * @param:
     *
     */
    public function addSection(Request $request){
        $section = json_decode($request->section);
        $idOriginal=$section->id;
        $section->name_section = $section->name_section.'_'.$request->cont;
        $section->duplicate = 0;
        $section->id = time();

        foreach ($section->fields as $element) {
            $element->value="";
            $element->duplicated = new stdClass();
            $element->duplicated->idOriginal = $element->id;
            $element->duplicated->Section = new stdClass();
            $element->duplicated->Section->id= $idOriginal;
            $element->duplicated->Section->name= $section->name_section;
            $element->duplicated->type = 'section';
            $element->id = intval($element->id.$request->cont);
            $element->key = $element->key.'_'.$request->cont;
            $element->label = $element->label.'_'.$request->cont;
            $element->disabled = false;
            foreach ($element->dependencies as $value) {
                $value->idField = intval($value->idField.$request->cont);
                $element->seeDepen = false;
            }
        }

        return json_encode($section);
    }

    /**
     * Consulta los formularios que contengan un campo tipo 'Agendamiento'
     *
     * @param  mixed $request
     * @return void
     */
    public function sectionCrmAgenda( Request $request ){
        $sections = Section::join("forms" , 'sections.form_id','=','forms.id' )
            ->select("name_form","fields","forms.id")
            ->get()
            ->filter( function($item){
                $fields = json_decode($item->fields);
                foreach ($fields as $field) {
                    if($field->type == "agendamiento"){
                        return true;
                    }
                }
                return false;
            } )->map( function($item){
                return $item->only(["id","name_form"]);
            } )->values();


        return $sections;
    }

    /**
     * @desc Busca los campos que son datos basicos del cliente en las sections y el identificador unico
     * @param integer id del formulario
     * @return object arreglo con los campos que son datos basicos del cliente (clientData) y su identificados unico
     * @return array El campo clientData contine las informaciones  preloaded, id, key, type, controlType y required
     * @author Joao Beleno
     */
    public function getDataClientInForm($idForm)
    {
        $form = Form::find($idForm);
        $result = [
            "fields_client_unique_identificator" => [],
            "clientData" => []
        ];
        foreach ($form->section as $section)
        {
            $fields = json_decode($section->fields);
            foreach ($fields as $field)
            {
                if(isset($field->isClientInfo) && $field->isClientInfo)
                {
                    $fieldClientData = (Object)[
                        "id" => $field->id,
                        "label" => $field->label,
                        "value" => '',
                        "type" => $field->type,
                        "controlType" => $field->controlType,
                        "required" => isset($field->required) && $field->required ? $field->required : false,
                    ];
                    array_push($result["clientData"], $fieldClientData);
                }
                if(isset($field->client_unique) && $field->client_unique)
                {
                    $fieldClient_unique = (Object)[
                        "id" => $field->id,
                        "label" => $field->label,
                        "key" => $field->key,
                        "value" => '',
                        "type" => $field->type,
                        "controlType" => $field->controlType,
                        "required" => true,
                        "isClientInfo" => true,
                        "preloaded" => true,
                        "client_unique" => true,
                    ];
                    $result["fields_client_unique_identificator"] = $fieldClient_unique;
                }
            }
        }
        return $result;
    }

    /**
     * Método para consultar el formulario con sus respectivas secciones
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co> 
     *
     * @param mixed $id
     * @return void
     */
    public function surveyForm()
    {
        $surveyFormId = env('SURVEY_FORM');
        $formsSections = Form::where('id', $surveyFormId)
            ->with(["section" => function($q){
                $q->where('state', '!=', 1);
            }])
            ->select('*')
            ->first();

        $formsSections->seeRoles = json_decode($formsSections->seeRoles);
        $formsSections->filters = json_decode($formsSections->filters);
        for ($i = 0; $i < count($formsSections->section); $i++) {
            unset($formsSections->section[$i]['created_at']);
            unset($formsSections->section[$i]['updated_at']);
            $formId = $formsSections->section[$i]['form_id'];
            $fields = json_decode($formsSections->section[$i]['fields']);

            $formsSections->section[$i]['fields'] = collect($fields)->filter(function ($field) use ($formId){
                return !$this->deletedFieldChecker($formId, $field->id);
            });

            unset($formId, $fields);
            unset($formsSections->section[$i]['form_id']);
        }
        $formsSections->client_unique = json_decode($formsSections->fields_client_unique_identificator);
        /**
         * Se agrega validacion de api_connections para integracion con SBS (DataCRM)
         */
        $formsSections->externalNotifications = false;
        $apiConnection = ApiConnection::where('form_id',$surveyFormId)->where('api_type',10)->where('status',1)->first();
        if($apiConnection) $formsSections->externalNotifications = true;
        $templateController = new TemplateController();
        $templateExist = (count($templateController->showByFormId($surveyFormId)) > 0);
        $formsSections->template = $templateExist;
        $formsSections->view_chronometer = (boolean)$formsSections->tipification_time;
        // $formsSections->count_assigned_clients = CustomerDataPreload::adviserFilter(auth()->user()->rrhh_id)
        // ->formFilter($id)->managedFilter(false)->get(['id'])->count();
        // $formsSections->count_assigned_clients += RelAdvisorClientNew::rrhhFilter(auth()->user()->rrhh_id)
        // ->join('client_news', 'client_news.id', 'rel_advisor_client_new.client_new_id')
        // ->where('client_news.form_id', $id)->where('rel_advisor_client_new.managed', false)->get(['rel_advisor_client_new.id'])->count();
        unset($formsSections->tipification_time);

        return response()->json($formsSections);
    }

    public function indexFormsByAdviser()
    {
        $forms = Form::join('form_types', 'forms.form_type_id', '=', 'form_types.id')
            ->join("groups", "groups.id", "forms.group_id")
            ->join('group_users', 'group_users.group_id', 'groups.id')
            ->join('tags', 'tags.form_id', 'forms.id')
            ->where('group_users.rrhh_id', auth()->user()->rrhh_id)->distinct()
            ->get(['name_form', 'forms.id', 'name_type', 'forms.state', 'seeRoles', 'forms.updated_at']);

       return response()->json($forms);
    }
}

