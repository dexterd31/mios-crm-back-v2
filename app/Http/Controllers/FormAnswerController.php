<?php

namespace App\Http\Controllers;

use App\Models\ApiConnection;
use App\Models\Client;
use App\Models\FormAnswer;
use App\Models\Form;
use App\Models\KeyValue;
use App\Models\Section;
use App\Models\Tray;
use App\Models\Attachment;
use App\Models\ClientNew;
use App\Models\FormAnswerLog;
use App\Models\User;
use App\Models\FormAnswerMiosPhone;
use App\Services\CiuService;
use App\Services\DataCRMService;
use App\Services\NominaService;
use Helpers\ApiHelper;
use Helpers\FilterHelper;
use Helpers\FormAnswerHelper;
use Helpers\MiosHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class FormAnswerController extends Controller
{
    private $ciuService;
    private $nominaService;
    private $dataCRMServices;

    public function __construct()
    {
        $this->middleware('auth');
        $this->ciuService = new CiuService();
        $this->nominaService = new NominaService();
        $this->dataCRMServices = new DataCRMService();
    }

    public function create($clientNewId, $formId, $structureAnswer, $formAnswerIndexData, $chronometer)
    {
        $formsAnswer = new FormAnswer([
            'rrhh_id' => auth()->user()->rrhh_id,
            'channel_id' => 1,
            'form_id' => $formId,
            'structure_answer' => json_encode($structureAnswer),
            'client_new_id' => $clientNewId,
            "form_answer_index_data" => json_encode($formAnswerIndexData),
            'tipification_time' => $chronometer
        ]);
        $saveFormAnswer=$this->saveModel($formsAnswer);
        // Guarda en Log FormAnswer
        $this->logFormAnswer($saveFormAnswer);
        return $saveFormAnswer;
    }

    public function saveinfo(Request $request)
    {
        $sections = json_decode($request['sections'], true);
        $clientNewInfo = [];
        $dataPreloaded = [];
        $formAnswerData = [];
        $formAnswerIndexData = [];
        $data = [];
        $date_string = Carbon::now()->format('YmdHis');
        foreach($sections as $section)
        {
            foreach($section['fields'] as $field)
            {
                $register=[];
                $register['id'] = $field['id'];
                $register['key'] = $field['key'];
                $register['value'] = $field['value'];
                $register['preloaded'] = $field['preloaded'];
                $register['label'] = $field['label'];
                $register['isClientInfo'] = isset($field['isClientInfo']) ? $field['isClientInfo'] : false;
                $register['client_unique'] = false;
                if($field['controlType'] == 'file'){
                    $attachment = new Attachment();
                    $attachment->name = $request->file($field['id'])->getClientOriginalName();
                    $attachment->source = $request->file($field['id'])->store($date_string);
                    $attachment->save();
                    $register['value'] = $attachment->id;
                    $register['nameFile']=$attachment->name; //Agregamos el nombre del archivo para que en el momento de ver las respuestas en el formulario se visualice el nombre.
                }

                if(isset($field['duplicated'])){
                    $register['duplicated']=$field['duplicated'];
                }
                if(json_decode($request->client_unique)[0]->id == $field['id'])
                {
                    if(!$field['value'])
                    {
                        $data["error"] = true;
                        $data["message"] = "El campo ". $field['label']." es el identificador unico del cliente y deve ser llenado.";
                    }
                    else
                    {
                        $register['client_unique'] = true;
                        $clientUnique = $register;
                    }
                }
                if($register['isClientInfo'] && !isset($data["error"]))
                {
                    array_push($clientNewInfo, [
                        "id" => $field['id'],
                        "value" => $field['value']
                    ]);
                }

                if(!empty($register['value'])  && !isset($data["error"]))
                {
                    if(isset($register['preloaded']) && $register['preloaded'])
                    {
                        array_push($dataPreloaded, $register);
                    }
                    array_push($formAnswerData, $register);
                    array_push($formAnswerIndexData, [
                        "id" =>$register["id"],
                        "value" =>$register["value"],
                    ]);
                }
            }
        }

        //creando nuevo cliente
        if(!isset($data["error"]))
        {
            $clientNewController = new ClientNewController();
            $clientNew = new Request();
            $clientNew->replace([
                "client_new_id" => $request->client_new_id,
                "form_id" => $request->form_id,
                "information_data" => json_encode($clientNewInfo),
                "unique_indentificator" => json_encode($clientUnique)
            ]);
            $clientNew = $clientNewController->create($clientNew);

            //creando nuevo cliente formAnswer
            $form_answer = $this->create($clientNew->id, $request->form_id, $formAnswerData, $formAnswerIndexData, $request->chronometer);
            $keyValueController = new KeyValueController();
            $keyValueController->createKeysValue($dataPreloaded, $request->form_id, $clientNew->id);

            // Manejar bandejas
            $this->matchTrayFields($form_answer->form_id, $form_answer);
            $this->updateDataCrm($clientNew->id, $form_answer);
            return $this->successResponse(['message'=>"Información guardada correctamente",'formAsnwerId'=>$form_answer->id]);
        }
        return $this->errorResponse($data["message"], 500);
    }

    /**
     * Si el fomulario tiene una integracion con DataCRM entonces la tipificacion será actualizada con DataCRM
     * @author Carlos Galindez
     */
    private function updateDataCrm($clientNewId, $form_answer)
    {
        $potentialIdObject = KeyValue::where('client_new_id',$clientNewId)->where('key','potential-id1')->first(); //Unique ID de Data CRM
        $accountIdObject = KeyValue::where('client_new_id',$clientNewId)->where('key','account-id0')->first(); //Unique ID de Data CRM

        if(ApiConnection::where('form_id',$form_answer->form_id)->where('api_type',10)->where('status',1)->first()  ){
            /**
             * Codigo Habilitado unicamente para pruebas, mientras DataCRM resuelve el bug
             */
            if($potentialIdObject) $this->dataCRMServices->updatePotentials($form_answer->form_id,json_decode($form_answer->structure_answer),$potentialIdObject->value);
            if($accountIdObject) $this->dataCRMServices->updateAccounts($form_answer->form_id,json_decode($form_answer->structure_answer),$accountIdObject->value);
        }
    }

    /**
     * @author Carlos Galindez
     * Metodo que permite guardar la tipificacion del formulario con el lead y UID de la llamada,
     * Esto pertenece a integracion con voz, (Vicidial)
     * @param leadId
     * @param phoneCustomer
     * @param uid
     * @param cui
     * @param form_answer_id
     */
    public function saveIntegrationVoice(Request $request){
        FormAnswerMiosPhone::create($request->all());
        return $this->successResponse('Datos guardados con exito');
    }

    public function filterForm(Request $request)
    {
        $miosHelper = new MiosHelper();
        $filterHelper = new FilterHelper();
        $requestJson = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $request->getContent()), true);
        $dataFilters = $this->getDataFilters($requestJson['filter']);
        $data = [];
        $files = [];

        $clientNewController = new ClientNewController();
        $clientNewData = new Request();
        $replace = [];
        if(isset($dataFilters["isClientInfo"]))
        {
            $replace["information_data"] = $dataFilters["isClientInfo"];
        }
        if(isset($dataFilters["client_unique"]))
        {
            $replace["unique_indentificator"] = json_encode($dataFilters["client_unique"][0]);
        }
        $replace["form_id"] = $request->form_id;

        $clientNewData->replace($replace);
        $clientNew = [];
        $clientNew = $clientNewController->index($clientNewData);


        if(!isset($clientNew["error"]))
        {
            $clientNewId = $clientNew ? $clientNew->id : null;
            $formAnswers = $this->filterFormAnswer($request->form_id, $requestJson['filter'], $clientNewId);
            $formAnswersData = $formAnswers->getCollection();
            if(count($formAnswersData) == 0)
            {
                $formAnswers = $filterHelper->filterByDataBase($request->form_id, $clientNewId, $requestJson['filter']);
                $formAnswersData = $formAnswers->getCollection();
            }
            if(count($formAnswersData) == 0)
            {
                $formAnswersApi = $filterHelper->filterbyApi($request->form_id, $requestJson['filter']);
                if(isset($formAnswersApi))
                {
                    $formAnswers = $formAnswersApi;
                    $formAnswersData = $formAnswers->getCollection();
                }
            }

            if(count($formAnswersData) > 0)
            {
                if(!$clientNewId)
                {
                    $clientNewId = $formAnswersData[0]->client_new_id;
                }

                $data = $this->setNewStructureAnswer($formAnswersData, $request->form_id);

                $formAnswersData = $data["formAnswers"];
                $files = $data["files"];
            }
            $data = $miosHelper->jsonResponse(true, 200, 'result', $formAnswers);
            if($clientNewId)
            {
                $data["preloaded"] = $this->preloaded($request->form_id, $clientNewId, $files);
            }
            return response()->json($data, $data['code']);
        }
        return $this->errorResponse('Error al buscar la gestion', 500);
    }

    private function getDataFilters($filters)
    {
        $dataFilters = [];
        $filds = ["isClientInfo", "preloaded", "client_unique"];
        foreach ($filters as $filter)
        {
            foreach ($filds as $fild)
            {
                if(isset($filter[$fild]))
                {
                    if(!isset($dataFilters[$fild]))
                    {
                        $dataFilters[$fild] = [];
                    }
                    array_push($dataFilters[$fild], $filter);
                }
            }
        }
        return $dataFilters;
    }

    private function setNewStructureAnswer($formAnswers, $formId)
    {
        foreach ($formAnswers as $formAnswer)
        {
            $files = [];
            $formAnswer['userdata'] = $this->ciuService->fetchUserByRrhhId($formAnswer['rrhh_id']);
            $structureAnswer = json_decode($formAnswer['structure_answer']);
            foreach ($structureAnswer as $answer) {
                if(!isset($answer->duplicated))
                {
                    $select = $this->findSelect($formId, $answer->id, $answer->value);
                    if($select)
                    {
                        $answer->value = $select;
                    }
                    $new_structure_answer[] = $answer;
                }
                if(isset($answer->nameFile) && $answer->nameFile && $answer->preloaded)
                {
                    array_push($files, (Object)[
                        "id" => $answer->id,
                        "key" => $answer->key,
                        "value" => $answer->value,
                        "nameFile" => $answer->nameFile,
                    ]);
                }
            }
            $formAnswer['structure_answer'] = $new_structure_answer;
        }
        return ["formAnswers" => $formAnswers, "files" => $files];
    }

    private function filterFormAnswer($formId, $filters, $clientNewId)
    {
        $formAnswersQuery = FormAnswer::where('form_id', $formId);
        foreach ($filters as $filter) {
            $filterData = [
                'id' => $filter['id'],
                'value' => $filter['value']
            ];
            $filterData = json_encode($filterData);
            $formAnswersQuery = $formAnswersQuery->whereRaw("json_contains(lower(form_answer_index_data), lower('$filterData'))");
        }
        if($clientNewId)
        {
            $formAnswersQuery = $formAnswersQuery->where("client_new_id", $clientNewId);
        }
        return $formAnswersQuery->paginate(5);
    }

    /**
     * Nicoll Ramirez
     * 22-02-2021
     * Método para consultar el tipo de documento en las respuestas del formulario
     */
    public function searchDocumentType()
    {
        $documentType = DB::table('document_types')
            ->select('id', 'name_type_document')->get();

        return $documentType;
    }

    /**
     * Jair Celis
     * 22-04-2021
     * Método para consultar un formAnswer
     * @param id Id del formAnswer a consultar
     */
    public function formAnswerHistoric($id, MiosHelper $miosHelper)
    {
        // try {
            $form_answers = FormAnswer::where('id', $id)->with('channel', 'clientNew')->first();

            $rrhhId = $form_answers->rrhh_id;
                $userData     = $this->ciuService->fetchUserByRrhhId($rrhhId);
                $form_answers->structure_answer = $miosHelper->jsonDecodeResponse($form_answers->structure_answer);

                $new_structure_answer = [];
                foreach($form_answers->structure_answer as $field){
                    if(isset($field['duplicated'])){
                        $select = $this->findSelect($form_answers->form_id, $field['duplicated']['idOriginal'], $field['value']);
                    }else{
                        $select = $this->findSelect($form_answers->form_id, $field['id'], $field['value']);
                    }
                    if($select){
                        $field['value'] = $select;
                        $new_structure_answer[] = $field;
                    } else {
                        $new_structure_answer[] = $field;
                    }
                }
                $form_answers->structure_answer = $new_structure_answer;
                $form_answers->user = $userData;

            $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
            return response()->json($data, $data['code']);
        // } catch (\Throwable $e) {
        //     return $this->errorResponse('Error al buscar la gestion', 500);
        // }
    }


    /**
     * Joao Beleno
     * 02-09-2021
     * @deprecated: Funcio no es utilizada
     */

    /**
     * Olme Marin
     * 29-03-2021
     * Método para actualizar al cleinte
     */
    public function updateClient($client)
    {
        $client->first_name         = isset($clientInfo[0]['firstName']) ? $clientInfo[0]['firstName'] : $client->first_name;
        $client->middle_name        = isset($clientInfo[0]['middleName']) ? $clientInfo[0]['middleName'] : $client->middle_name;
        $client->first_lastname     = isset($clientInfo[0]['lastName']) ? $clientInfo[0]['lastName'] : $client->first_lastname;
        $client->second_lastname    = isset($clientInfo[0]['secondLastName']) ? $clientInfo[0]['secondLastName'] : $client->second_lastname;
        $client->phone              = isset($clientInfo[0]['phone']) ? $clientInfo[0]['phone'] : $client->phone;
        $client->email              = isset($clientInfo[0]['email']) ? $clientInfo[0]['email'] : $client->email;
        $client->update();
    }

    public function updateInfo(Request $request, $id){
        $obj = array();
        $i=0;
        $date_string = Carbon::now()->format('YmdHis');
        foreach ($request->sections as $section) {
            foreach ($section['fields'] as $field) {
                $register=[];
                if ($i == 0) {
                    $clientData[$field['key']] = $field['value'];
                }
                $register['id'] = $field['id'];
                $register['key'] = $field['key'];
                $register['value'] = $field['value'];
                $register['preloaded'] = $field['preloaded'];
                $register['label'] = $field['label'];//Campo necesario para procesos de sincronizacion con DataCRM

                //manejo de adjuntos
                /*if($field['controlType'] == 'file'){
                    $attachment = new Attachment();
                    $attachment->name = $request->file($field['id'])->getClientOriginalName();
                    $attachment->source = $request->file($field['id'])->store($date_string);
                    $attachment->save();
                    $register['value'] = $attachment->id;
                    $register['nameFile']=$attachment->name; //Agregamos el nombre del archivo para que en el momento de ver las respuestas en el formulario se visualice el nombre.
                }*/

                if(isset($field['duplicated'])){
                    $register['duplicated']=$field['duplicated'];
                }

                if(!empty($register['value'])){
                    array_push($obj, $register);
                }
            }
            $i++;
        }

        $form_answer = FormAnswer::where('id', $id)->first();

        $form_answer->structure_answer = json_encode($obj);
        $form_answer->update();

        // Manejar bandejas
        $this->matchTrayFields($form_answer->form_id, $form_answer);

        // Log FormAnswer
        $this->logFormAnswer($form_answer);

        return response()->json('Guardado' ,200);
    }

    /**
     * revisa la bandeja a ver si hay salida o entrada de la gestion a una bandeja
     */
    public function matchTrayFields($formId, $formAnswer){

        $trays = Tray::where('form_id',$formId)
                        ->get();

        foreach ($trays as $tray) {

            /* entrada a bandeja */
            $in_fields_matched = 0;
            foreach(json_decode($tray->fields) as $field){

                $estructura = json_decode($formAnswer->structure_answer);
                // Filtrar que contenga el id del field buscado
                $tray_in = collect($estructura)->filter( function ($value, $key) use ($field) {
                    // si es tipo options, validar el valor del option
                    if($field->type == "options"){
                        if($value->id==$field->id){
                            $validate = false;
                            foreach($field->value as $fieldValue){
                                if($value->value == $fieldValue->id){
                                    $validate = true;
                                    // return 1;
                                // }else{
                                //     if($validate == true){
                                //         $validate = true;
                                //     }else{
                                //         $validate = false;
                                //     }
                                //     // return 0;
                                }
                            }
                            if($validate == true){
                                return 1;
                            }else{
                                return 0;

                            }
                        }
                    }else{
                        // si es otro tipo validar que el valor no este vacio o nulo.
                        if($value->id==$field->id && !empty($value->value)){
                            return 1;
                        }else{
                            return 0;
                        }
                    }

                });

                if(count($tray_in)>=1){
                    $in_fields_matched++;
                }
            }

            if((count(json_decode($tray->fields))> 0) && ($in_fields_matched == count(json_decode($tray->fields)))){
                if(!$tray->FormAnswers->contains($formAnswer->id)){
                    $tray->FormAnswers()->attach($formAnswer->id);
                }
            }

            /* salida a bandeja */
            $exit_fields_matched = 0;
            foreach(json_decode($tray->fields_exit) as $field_exit){

                $estructura = json_decode($formAnswer->structure_answer);
                // Filtrar que contenga el id del field buscado
                $tray_out = collect($estructura)->filter( function ($value, $key) use ($field_exit) {
                    // si es tipo options, validar el valor del option
                    if($field_exit->type == "options"){
                        if($value->id==$field_exit->id){
                            $validate = false;
                            foreach($field_exit->value as $fieldValue){
                                if($value->value == $fieldValue->id){
                                    $validate = true;
                                }
                            }
                            if($validate == true){
                                return 1;
                            }else{
                                return 0;
                            }
                        }
                    }else{
                        // si es otro tipo validar que el valor no este vacio o nulo.
                        if($value->id==$field_exit->id && !empty($value->value)){
                            return 1;
                        }else{
                            return 0;
                        }
                    }

                });

                if(count($tray_out)>=1){
                    $exit_fields_matched++;
                }
            }
            if((count(json_decode($tray->fields_exit)) >0 ) && ($exit_fields_matched == count(json_decode($tray->fields_exit)))){
                $tray->FormAnswers()->detach($formAnswer->id);
            }
        }

    }

    private function logFormAnswer($form_answer)
    {
        $log = new FormAnswerLog();
        $log->form_answer_id = $form_answer->id;
        $log->structure_answer = $form_answer->structure_answer;
        $log->rrhh_id = $form_answer->rrhh_id;
        $log->save();
    }

    public function downloadFile(Request $request)
    {
        $attachment = Attachment::findOrfail($request->url);
        return response()->download(storage_path("app/" . $attachment->source), $attachment->name);
    }

    private function preloaded($form_id, $client_new_id, $files)
    {
        $form = Form::find($form_id);
        $structure_data = [];
        foreach($form->section as $section){
            $section->fields =json_decode($section->fields);
            foreach ( $section->fields as $field) {
                if($field->preloaded == true && $field->controlType != "file"){
                    //Traemos descripcion pues alli se guarda el nombre del archivo
                    $key_value = KeyValue::where('client_new_id', $client_new_id)->where('field_id', $field->id)->select('field_id', 'value', 'key', 'description')->latest()->first();
                    if($key_value){
                        $key_value->id = $key_value->field_id;
                        unset($key_value->field_id);
                        $structure_data[] = $key_value;
                    }
                }
            }
        }
        $answer['data'] = array_merge($structure_data, $files);
        $answer['client_id']=$client_new_id;
        return $answer;

    }

    private function findSelect($form_id, $field_id, $value)
    {
        $sections = Section::where('form_id', $form_id)
        ->whereJsonContains('fields', ['id' => $field_id])
        ->first();
        if(!$sections)
        {
            return null;
        }
        $fields = $sections->fields;

        $field = collect(json_decode($fields))->filter(function($x) use ($field_id){
            return $x->id == $field_id;
        })->first();

        if($field->controlType == 'dropdown'){
            $field_name = collect($field->options)->filter(function($x) use ($value){
                return $x->id == $value;
            })->first()->name;
            return $field_name;
        } else {
            return null;
        }

    }
}
