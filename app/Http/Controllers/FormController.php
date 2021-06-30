<?php

namespace App\Http\Controllers;

use App\Exports\FormReportExport;
use App\Models\ApiConnection;
use App\Models\Form;
use App\Models\FormLog;
use App\Models\FormAnswer;
use App\Models\FormType;
use App\Models\KeyValue;
use App\Models\Section;
use App\Models\User;
use App\Services\CiuService;
use Helpers\MiosHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use Carbon\Carbon;

class FormController extends Controller
{
    private $ciuService;

    public function __construct(CiuService $ciuService)
    {
        $this->middleware('auth');
        $this->ciuService = $ciuService;
    }

    /**
     * Nicol Ramirez
     * 27-01-2020
     * Método para consultar los formularios existentes en la BD
     */
    public function FormsList(Request $request)
    {
        $userId = auth()->user()->rrhh_id;
        $userLocal = User::where('id_rhh','=',$userId)->firstOrFail();
        $roles = auth()->user()->roles;
        $rolesArray = [];
        foreach ($roles as $value) {
            if (str_contains($value, 'crm::')) {
                $rolesArray[] = str_replace('crm::', '', $value);
            }
        }
        $paginate = $request->query('n', 5);
        $forms = $this->getFormsByIdUser($userLocal->id, $paginate);

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
            ->with('section')
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
        /**
         * Se agrega validacion de api_connections para integracion con SBS (DataCRM)
         */
        $formsSections->externalNotifications = false;
        $apiConnection = ApiConnection::where('form_id',$id)->where('api_type',10)->where('status',1)->first();
        if($apiConnection) $formsSections->externalNotifications = true;

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
            $forms = new Form([
                'group_id' =>  $request->input('group_id'),
                'campaign_id' => $request->input('campaign_id'),
                'form_type_id' => $request->input('type_form'),
                'name_form' => $request->input('name_form'),
                'filters' => json_encode($request->filters),
                'state' => $request->state,
                'seeRoles' => json_encode($request->role)
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
                    }
               }

              if($section['sectionName'] == 'Datos básicos del cliente')
              {
                $firstSection = new Section([
                    'id' => $section['idsection'],
                      'form_id' => $forms->id,
                      'name_section' => $section['sectionName'],
                      'type_section' => $section['type_section'],
                      'fields' => json_encode($section['fields']),
                      'collapse' => empty($section['collapse'])? 0 : $section['collapse']
                    ]);
                    $firstSection->save();
                }else{
                    $fields = $section['fields'];
                    $sections = new Section([
                        'id' => $section['idsection'],
                        'form_id' => $forms->id,
                        'name_section' => $section['sectionName'],
                        'type_section' => $section['type_section'],
                        'fields' => json_encode($fields),
                        'collapse' => empty($section['collapse'])? 0 : $section['collapse']
                    ]);
                    $sections->save();
                }
            }
            if(!isset($sections)){
                $data = ['forms' => $forms , 'firstSection'=> json_decode($firstSection->fields), 'code' => 200,'message'=>'Formulario Guardado Correctamente'];
            }else{
                $data = ['forms' => $forms , 'firstSection'=> json_decode($firstSection->fields),'sections' => json_decode($sections->fields), 'code' => 200,'message'=>'Formulario Guardado Correctamente'];
            }

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

            foreach($request->sections as $section)
            {
                for($i=0; $i<count($section['fields']); $i++){
                    $cadena = (string)$i;
                    if($section['fields'][$i]['key'] == 'null'){
                        $section['fields'][$i]['key'] = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'],$section['fields'][$i]['label']);
                       $section['fields'][$i]['key'] =  strtolower( str_replace(' ','-',$section['fields'][$i]['label']) );
                       $section['fields'][$i]['key'] = $section['fields'][$i]['key'].$cadena;
                    }
                }


                if($section['sectionName'] == 'Datos básicos de cliente'){
                    $sections = Section::find($section['idsection']);
                    $sections->name_section = $section['sectionName'];
                    $sections->type_section = $section['type_section'];
                    $sections->fields = json_encode($section['fields']);
                    $sections->collapse = empty($section['collapse'])? 0 : $section['collapse'];
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
                        'collapse' => empty($section['collapse'])? 0 : $section['collapse']
                        ]);
                        $sections->save();

                    }else{
                        $sections->name_section = $section['sectionName'];
                        $sections->type_section = $section['type_section'];
                        $sections->fields = json_encode($fields);
                        $sections->collapse = empty($section['collapse'])? 0 : $section['collapse'];
                        $sections->save();
                    }

                }
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
      $sections=Section::select('fields')->where("form_id",$request->formId)->get();
      $formAnswers = FormAnswer::where('form_id',$request->formId)
                          ->where('created_at','>=', $request->date1)
                          ->where('created_at','<=', $request->date2)
                          ->select('id', 'structure_answer', 'created_at', 'updated_at')->get();
      if(count($formAnswers)==0){
            // 406 Not Acceptable
            // se envia este error ya que no esta mapeado en interceptor angular.
            return $this->errorResponse('No se encontraron datos en el rango de fecha suministrado', 406);
      } else if(count($formAnswers)>1000){
            return $this->errorResponse('El rango de fechas supera a los 1000 records', 413);
      } else {
        $inputReport=[];
        $titleHeaders=['Id'];
        $dependencies=[];
        $r=0;
        $rows=[];
        //Verificamos cuales son los campos que deben ir en el reporte o que su elemento inReport sea true
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
                        }
                        $input->dependencies[0]->report=$input->label;
                    }else{
                        array_push($titleHeaders,$input->label);
                        array_push($inputReport,$input);
                    }
                }
            }
        }

        foreach($formAnswers as $answer){
            $rows[$r]['id'] = $answer->id;
            //Evaluamos los campos que deben ir en el reporte contra las respuestas
            foreach($inputReport as $input){
                foreach(json_decode($answer->structure_answer) as $field){
                    if(isset($input->dependencies[0]->report)){
                        if(in_array($field->id,$dependencies[$input->dependencies[0]->report])){
                            $select = $this->findAndFormatValues($request->formId, $field->id, $field->value);
                            if($select){
                                $rows[$r]['Dependencias'] = $select;
                            } else {
                                $rows[$r]['Dependencias'] = $field->value;
                            }
                            break;
                        }
                    }else if($field->id==$input->id){
                        $select = $this->findAndFormatValues($request->formId, $field->id, $field->value);
                        if($select){
                            $rows[$r][$field->id] = $select;
                        } else {
                            $rows[$r][$field->id] = $field->value;
                        }
                        break;
                    }else if($field->key==$input->key){
                        $select = $this->findAndFormatValues($request->formId, $input->id, $field->value);
                        if($select){
                            $rows[$r][$input->id] = $select;
                        } else {
                            $rows[$r][$input->id] = $field->value;
                        }
                        break;
                    }
                }
                if(!isset($rows[$r][$input->id])){
                    $rows[$r][$input->id]="-";
                }
            }
            $rows[$r]['created_at'] = Carbon::parse($answer->created_at->format('c'))->setTimezone('America/Bogota');
            $rows[$r]['updated_at'] = Carbon::parse($answer->updated_at->format('c'))->setTimezone('America/Bogota');
            $r++;
          }
          array_push($titleHeaders,'Fecha de creación','Fecha de actualización');
      }
      return Excel::download(new FormReportExport($rows, $titleHeaders), 'reporte_formulario.xlsx');
    }

    /**
     * Olme Marin
     * 25-03-2021
     * Método para consultar el listado de los formularios asignados a un usuario por grupo
     * @deprecated: La función FormList ya realiza la busqueda por usuarios y grupos Reportada 2021-06-10
     */
    public function formsByUser(MiosHelper $miosHelper, $idUser, Request $request)
    {
        $paginate = $request->query('n', 5);
        $forms = $this->getFormsByIdUser($idUser, $paginate);
        foreach ($forms as $form) {
            $form->filters = $miosHelper->jsonDecodeResponse($form->filters);
        }
        $data = $miosHelper->jsonResponse(true, 200, 'forms', $forms);
        return response()->json($data, $data['code']);
    }

    private function logForm($form, $sections)
    {
        $user = auth()->user()->rrhh_id;
        $log = new FormLog();
        $log->group_id = $form->group_id ;
        $log->campaign_id = $form->campaign_id ;
        $log->name_form = $form->name_form ;
        $log->filters = $form->filters ;
        $log->state = $form->state ;
        $log->sections = json_encode($sections) ;
        $log->user_id = $user ;
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
                    if($j>=7){
                       if($fields[$j]->preloaded == false){
                            unset($fields[$j]);
                       }
                    }
                }
            }
            else{
                $fields = $fields->filter(function($x){
                                return $x->preloaded == true;
                            });
            }

            $formsSections->section[$i]['fields'] = array_values($fields->toArray());
        }

        return response()->json($formsSections);



    }

    private function findAndFormatValues($form_id, $field_id, $value)
    {

        $fields = json_decode(Section::where('form_id', $form_id)
        ->whereJsonContains('fields', ['id' => $field_id])
        ->first()->fields);
        $field = collect($fields)->filter(function($x) use ($field_id){
            return $x->id == $field_id;
        })->first();

        if($field->controlType == 'dropdown' || $field->controlType == 'autocomplete' || $field->controlType == 'radiobutton'){
            $field_name = collect($field->options)->filter(function($x) use ($value){
                return $x->id == $value;
            })->first()->name;
            return $field_name;
        }elseif($field->controlType == 'datepicker'){
            return Carbon::parse($value)->setTimezone('America/Bogota')->format('Y-m-d');
        }else {
            return null;
        }
    }

    private function getFormsByIdUser($userId, $paginate)
    {
        $forms = Form::join('form_types', 'forms.form_type_id', '=', 'form_types.id')
            ->join("groups", "groups.id", "forms.group_id")
            ->join('group_users', 'group_users.group_id', 'groups.id')
            ->select('name_form', 'forms.id', 'name_type', 'forms.state', 'seeRoles', 'forms.updated_at')
            ->where('group_users.user_id', $userId)
            ->paginate($paginate)->withQueryString();
        return $forms;
    }
}
