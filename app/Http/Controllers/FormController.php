<?php

namespace App\Http\Controllers;

use App\Exports\FormReportExport;
use App\Models\ApiConnection;
use App\Models\Form;
use App\Models\FormLog;
use App\Models\FormAnswer;
use App\Models\FormAnswerLog;
use App\Models\FormType;
use App\Models\Section;
use App\Models\Tray;
use App\Services\RrhhService;
use Helpers\MiosHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;


class FormController extends Controller
{

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
     * Nicol Ramirez
     * 27-01-2020
     * Método para consultar el formulario con sus respectivas secciones
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
            unset($formsSections->section[$i]['form_id']);
            $formsSections->section[$i]['fields'] = json_decode($formsSections->section[$i]['fields']);
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


    public function getStateForms($sectionNames,$id){

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
    public function report(Request $request, MiosHelper $miosHelper){
        $char="";
        $rrhhService = new RrhhService();
        $trayHistoric = Tray::select('id')->where('form_id',$request->formId)->whereNotNull('save_historic')->get();
        if(count($trayHistoric)>0){
            $formAnswers = DB::table('form_answer_logs')
                           ->join('form_answers','form_answer_logs.form_answer_id','=','form_answers.id')
                           ->where('form_answers.form_id','=',$request->formId)
                           ->where('form_answers.tipification_time', '!=', 'upload')
                           ->whereBetween('form_answers.created_at', ["$request->date1 00:00:00", "$request->date2 00:00:00"])
                        ->select('form_answer_logs.form_answer_id as id', 'form_answer_logs.structure_answer', 'form_answer_logs.created_at', 'form_answer_logs.updated_at','form_answer_logs.rrhh_id as id_rhh', 'form_answers.tipification_time')
                        ->get();
        }else{
            $formAnswers = FormAnswer::select('form_answers.id', 'form_answers.structure_answer', 'form_answers.created_at', 'form_answers.updated_at','form_answers.rrhh_id as id_rhh','tipification_time')
                            ->where('form_answers.form_id',$request->formId)
                            ->where('tipification_time','!=','upload')
                            ->whereBetween('form_answers.created_at', ["$request->date1 00:00:00", "$request->date2 00:00:00"])
                            ->get();
        }
        if(count($formAnswers)==0){
            // 406 Not Acceptable
            // se envia este error ya que no esta mapeado en interceptor angular.
            return $this->errorResponse('No se encontraron datos en el rango de fecha suministrado', 406);
        } else if(count($formAnswers)>5000){
            return $this->errorResponse('El rango de fechas supera a los 5000 records', 413);
        } else {
            $inputReport=[];
            $titleHeaders=['Id'];
            $dependencies=[];
            $r=0;
            $rows=[];
            $plantillaRespuestas=[];
            //Agrupamos los id_rrhh del usuario en un arreglo
            $userIds=$miosHelper->getArrayValues('id_rhh',$formAnswers);
            $useString=implode(',',array_values(array_unique($userIds)));
            //Traemos los datos de rrhh de los usuarios
            $usersInfo=$rrhhService->fetchUsers($useString);
            //Organizamos la información del usuario en un array asociativo con la información necesaria
            $adviserInfo=[];
            foreach($usersInfo as $info){
                if(in_array($info->id,$userIds)){
                    if(!isset($adviserInfo[$info->id])){
                        $adviserInfo[$info->id]=$info;
                    }
                }
            }
            //Verificamos cuales son los campos que deben ir en el reporte o que su elemento inReport sea true
            $sections=Section::select('fields')->where("form_id",$request->formId)->get();
            $plantillaRespuestas['id']=$char;
            foreach($sections as $section){
                foreach(json_decode($section->fields) as $input){
                    if($input->inReport){
                        if(count($input->dependencies)>0){
                            if(isset($dependencies[$input->label])){
                                array_push($dependencies[$input->label],$input->id);
                            }else{
                                $dependencies[$input->label]=[$input->id];
                                array_push($titleHeaders,$input->label);
                                array_push($inputReport,$input);
                                $plantillaRespuestas[$input->label]=$char;
                            }
                            $input->dependencies[0]->report=$input->label;
                        }else{
                            array_push($titleHeaders,$input->label);
                            array_push($inputReport,$input);
                            $plantillaRespuestas[$input->id]=$char;
                        }
                    }
                }
            }
            $plantillaRespuestas['user']=$char;
            $plantillaRespuestas['docuser']=$char;
            $plantillaRespuestas['created_at'] =$char;
            $plantillaRespuestas['updated_at'] =$char;

            foreach($formAnswers as $answer){
                $respuestas=$plantillaRespuestas;
                $respuestas['id'] = $answer->id;
                //Evaluamos los campos que deben ir en el reporte contra las respuestas
                foreach($inputReport as $input){
                    foreach(json_decode($answer->structure_answer) as $field){
                        if(isset($input->dependencies[0]->report)){
                            if(in_array($field->id,$dependencies[$input->dependencies[0]->report])){
                                if(isset($field->value)){
                                    $select = $this->findAndFormatValues($request->formId, $field->id, $field->value);
                                        if($select->valid && isset($select->name)){
                                            $respuestas[$input->dependencies[0]->report] = $select->name;
                                        } else {
                                            $respuestas[$input->dependencies[0]->report] = json_encode($select);
                                        }
                                }
                                break;
                            }
                        }else if($field->id==$input->id){
                            $select = $this->findAndFormatValues($request->formId, $field->id, $field->value);
                            if($select->valid && isset($select->name)){
                                $respuestas[$input->id] = $select->name;
                            } else {
                                $respuestas[$input->id] = json_encode($select);
                            }
                            break;
                        }else if($field->key==$input->key){
                            $select = $this->findAndFormatValues($request->formId, $input->id, $field->value);
                            if($select->valid && isset($select->name)){
                                $respuestas[$input->id] = $select->name;
                            } else {
                                $respuestas[$input->id] = json_encode($select);
                            }
                            break;
                        }
                    }
                }
                $respuestas['user']=$char;
                $respuestas['docuser']=$char;
                if(isset($adviserInfo[$answer->id_rhh]->name)){
                    $respuestas['user']=$adviserInfo[$answer->id_rhh]->name;
                    $respuestas['docuser']=$adviserInfo[$answer->id_rhh]->id_number;
                }
                if(gettype($answer->created_at)=='object'){
                    $respuestas['created_at'] = Carbon::parse($answer->created_at->format('c'))->setTimezone('America/Bogota');
                    $respuestas['updated_at'] = Carbon::parse($answer->updated_at->format('c'))->setTimezone('America/Bogota');
                }else{
                    $respuestas['created_at'] = $answer->created_at;
                    $respuestas['updated_at'] = $answer->updated_at;
                }
                if(isset($request->include_tipification_time) && $request->include_tipification_time){
                    $respuestas['tipification_time'] = $answer->tipification_time;
                }
                $rows[$r]=$respuestas;
                $r++;
            }
            array_push($titleHeaders,'Asesor','Documento Asesor','Fecha de creación','Fecha de actualización');
            if(isset($request->include_tipification_time) && $request->include_tipification_time){
                array_push($titleHeaders,'Tiempo de tipificación');
            }else{
                \Log::warning("Parametro include_tipification_time ".isset($request->include_tipification_time)."para generar el reporte");
            }
        }
        return Excel::download(new FormReportExport($rows, $titleHeaders), 'ReporteFormulario.xlsx');
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

    /**
     * Valida que el field exista en el formulario, valida el tipo de dato y lo formatea de ser necesario,
     * @param $form_id : id del fomulario
     * @param $field_id: id del field a consultar
     * @param $value: valor del field que se está validando
     * @return stdClass : objeto que puede contener los siguientes atributos:
     *                      -   valid (boolean) : indica si la validación fue exitosa
     *                      -   value : retorna el valor formateado en caso que el atributo valid sea verdadero
     *                      -   message : retorna el mensaje de error en caso que el atributo valid sea falso
     */
    public function findAndFormatValues($form_id, $field_id, $value, $moneyConvert = false)
    {
        $response = new stdClass();
        $response->valid = false;
        $response->message = "";
        $fields = json_decode(Section::where('form_id', $form_id)
        ->whereJsonContains('fields', ['id' => $field_id])
        ->first()->fields);

        if(count($fields) == 0){
            $response->message = "field not found";
            return $response;
        }
        $field = collect($fields)->filter(function($x) use ($field_id){
            return $x->id == $field_id;
        })->first();
        if(empty($field)){
            $response->message = "field not found";
            return $response;
        }
        if(($field->controlType == 'dropdown' || $field->controlType == 'autocomplete' || $field->controlType == 'radiobutton')){
            $field_name = collect($field->options)->filter(function($x) use ($value){
                if(intval($value) == 0){
                    return $x->name == $value;
                }
                return $x->id == $value;
            })->first();
            if($field_name){
                $response->valid = true;
                $response->value = $field_name->id;
                $response->name = $field_name->name;
                return $response;
            }
            $response->message = "value $value not match";
            return $response;
        }elseif($field->controlType == 'datepicker'){
            if($value !="Invalid date"){
                $date = "";
                try {
                    if(is_int($value)){
                       //Se suma un dia pues producción le resta un dia a las fechas formato date de excel
                        $unix_date = (($value+1) - 25569) * 86400;
                        $date = Carbon::createFromTimestamp($unix_date)->format('Y-m-d');
                    }else{
                        $date = Carbon::parse(str_replace("/","-",$value))->format('Y-m-d');
                    }
                    $response->valid = true;
                    $response->value = $date;
                }catch (\Exception $ex){
                    $response->valid = false;
                    $response->message = "date $value is not a valid format";
                }
            }else{
                $response->valid = true;
                $response->value = '';
            }
            return $response;
        }elseif($field->controlType == 'file'){
            $attachmentController = new AttachmentController();
            $attachment = $attachmentController->show($value);
            $response->valid = true;
            $response->value = url().'/api/attachment/downloadFile/'.$attachment->id;
            return $response;
        }elseif($field->controlType == 'multiselect'){
            $multiAnswer=[];
            foreach($value as $val){
                $field_name = collect($field->options)->filter(function($x) use ($val){
                    return $x->id == $val;
                })->first()->name;
                array_push($multiAnswer,$field_name);
            }
            $response->valid = true;
            $response->value = implode(",",$multiAnswer);
            return $response;
        }elseif($field->controlType == 'currency'){
            $response->valid = true;
            if($moneyConvert){
                $response->value = number_format(intval($value));
                return $response;
            }
            $response->value = str_replace(",","",$value);
            return $response;
        }else{
            $response->valid = true;
            $response->value = $value;
            return $response;
        }

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
     * @desc Función para devolver las secciones de un formulario
     * @param Integer $formId id del formulario que se necesitan traer las secciones
     * @return Array Arreglo de objetos en donde se encuntran todas las secciones del formulario
     * @author Leonardo Giraldo Quintero
     *  */
    public function getSections($formId){
        if(isset($formId)){
            return Section::where('form_id','=',$formId)->get();
        }else{
            return "Error al definir la variable formId";
        }

    }

    /**
     * @desc Busca los fields por su id en las secciones
     * @param array $search Arreglo de objetos, cada objeto debe contener los elementos id: numero del field al que pertenece
     * @param integer $formId Numero entero con el id del formulario al que se le debe realizar la busqueda de fields
     * @return array arreglo con los field solicitados con toda su estructura
     * @author Leonardo Giraldo Quintero
     */
    public function getSpecificFieldForSection($searchIdFileds , $formId){
        $completeFileds=[];
        $sections = $this->getSections($formId);
        if(count($sections)>0){
            foreach($sections as $section){
                foreach(json_decode($section->fields) as $field){
                    foreach($searchIdFileds as $search){
                        if($search->id==$field->id){
                            $completeFileds[$field->id]=$field;
                        }
                    }
                }
            }
            return $completeFileds;
        }
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
}

