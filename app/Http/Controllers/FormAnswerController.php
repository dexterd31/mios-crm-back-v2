<?php

namespace App\Http\Controllers;

use App\Managers\ClientsManager;
use App\Managers\TrafficTrayManager;
use App\Models\ApiConnection;
use App\Models\Directory;
use App\Models\FormAnswer;
use App\Models\FormAnswersTrayHistoric;
use App\Models\KeyValue;
use App\Models\Section;
use App\Models\Tray;
use App\Models\Attachment;
use App\Models\CustomerDataPreload;
use App\Models\FormAnswerLog;
use App\Models\FormAnswerMiosPhone;
use App\Services\CiuService;
use App\Services\DataCRMService;
use App\Services\NominaService;
use Helpers\FilterHelper;
use Helpers\MiosHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\FormAnswersTray;
use App\Models\RelAdvisorClientNew;
use App\Models\RelTrayUser;
use App\Traits\CheckDuplicateSections;
use App\Traits\deletedFieldChecker;
use App\Traits\FieldsForSection;
use App\Traits\FindAndFormatValues;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class FormAnswerController extends Controller
{
    use deletedFieldChecker, CheckDuplicateSections;
    use FieldsForSection, FindAndFormatValues;
    
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

    public function create($clientNewId, $formId, $structureAnswer, $formAnswerIndexData, $chronometer, $chanel_id = 1, $conversation_id = null)
    {
        $saveFormAnswer = new FormAnswer();
        $saveFormAnswer->rrhh_id = auth()->user()->rrhh_id;
        $saveFormAnswer->channel_id = $chanel_id;
        $saveFormAnswer->form_id = $formId;
        $saveFormAnswer->structure_answer = json_encode($structureAnswer);
        $saveFormAnswer->client_new_id = $clientNewId;
        $saveFormAnswer->form_answer_index_data = json_encode($formAnswerIndexData);
        $saveFormAnswer->tipification_time = $chronometer;
        $saveFormAnswer->conversation_id = $conversation_id;
        $saveFormAnswer->save();
        /*$saveFormAnswer= FormAnswer::updateOrCreate([
            'rrhh_id' => auth()->user()->rrhh_id,
            'form_id' => $formId,
        ],[
            'channel_id' => 1,
            'structure_answer' => json_encode($structureAnswer),
            'client_new_id' => $clientNewId,
            "form_answer_index_data" => json_encode($formAnswerIndexData),
            'tipification_time' => $chronometer
        ]);*/
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
        $formAnswerTrayHistoric =[];
        $data = [];
        $date_string = Carbon::now()->format('YmdHis');
        foreach($sections as $section)
        {
            foreach($section['fields'] as $field)
            {
                $register=[];
                $register['id'] = $field['id'];
                $register['key'] = $field['key'];
                if($field['controlType'] == "currency"){
                    $field['value']=str_replace(",","",$field['value']);
                }

                if ($field['controlType'] == 'multiselect') {
                    if (gettype($field['value']) != 'array') {
                        Log::error("TIPO DE DATO ERRONEO EN EL MULTISELECT, DATA DE LA SECCIÓN: \n" . json_encode($section));
                        $field['value'] = [ $field['value'] ];
                    }
                }

                $register['value'] = $field['value'];
                $register['section_id'] = $section['id'];
                $register['preloaded'] = $field['preloaded'];
                $register['label'] = $field['label'];
                $register['isClientInfo'] = isset($field['isClientInfo']) ? $field['isClientInfo'] : false;
                $register['client_unique'] = false;
                if($field['controlType'] == 'file' && $field['value'] !=''){
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
                /**
                 * se agrega la validacion del elemento conversation_id para almacenar el id de la conversacion por la que se relaiza la encuesta de o nicanalidad
                 */
                if(isset($field['conversation_id'])){
                    $register['conversation_id']=$field['conversation_id'];
                }
                if(json_decode($request->client_unique)[0]->id == $field['id'])
                {
                    if(!$field['value'])
                    {
                        $data["error"] = true;
                        $data["message"] = "El campo ". $field['label']." es el identificador unico del cliente y debe ser llenado.";
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
                    if(isset($field['tray'])){
                        $register['tray'] = $field['tray'];
                        array_push($formAnswerTrayHistoric, $register);
                    }else{
                        array_push($formAnswerData, $register);
                    }
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
            if(!isset($request->chanel)){
                $request->chanel = 1;
            }
            if(!isset($request->conversation_id)){
                $request->conversation_id = null;
            }

            $form_answer = $this->create($clientNew->id, $request->form_id, $formAnswerData, $formAnswerIndexData, $request->chronometer,$request->chanel,$request->conversation_id);

            $keyValueController = new KeyValueController();
            $keyValueController->createKeysValue($dataPreloaded, $request->form_id, $clientNew->id);

            // Manejar bandejas
            $this->matchTrayFields($form_answer->form_id, $form_answer);
            $this->updateDataCrm($clientNew->id, $form_answer);

            //Manejar Escalamientos
            //Evita otro llamado a el back para saber si la tipificación realizada debe escalarse o no.
            $scalationController = new EscalationController();
            $scalationRequest = new Request();
            $scalationRequest->replace([
                "form" => $request['sections'],
                "form_id" => $request->form_id,
                "client_id" => $clientNew->id
            ]);
            $scalationController->validateScalation($scalationRequest);

            //validarNotificaciones
            $notificationsController = new NotificationsController();
            $notificationsController->sendNotifications($request->form_id,$form_answer);

            if(!is_null($request->client_id)){
                $relAdvisorClientNew = RelAdvisorClientNew::rrhhFilter(auth()->user()->rrhh_id)->where('client_new_id', $request->client_id)->first();
    
                if (!is_null($relAdvisorClientNew)) {
                    $relAdvisorClientNew->managed = true;
                    $relAdvisorClientNew->save();
                }
            }

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
        $filter = $request->filter;
        $dataFilters = $this->getDataFilters($filter);
        $data = [];
        $files = [];
        $replace["form_id"] = $request->form_id;

        $clientNewController = new ClientNewController();
        $clientNewData = new Request();
        
        if(isset($dataFilters["isClientInfo"]))
        {
            $replace["information_data"] = $dataFilters["isClientInfo"];
        }
        
        if(isset($dataFilters["client_unique"]))
        {
            $this->processPreloadedData($replace["form_id"], (object) $dataFilters["client_unique"][0]);
            $replace["unique_indentificator"] = json_encode($dataFilters["client_unique"][0]);
        }

        if(isset($replace["form_id"]) && (isset($replace["information_data"]) || isset($replace["unique_indentificator"]))){
            $clientNewData->replace($replace);
            $clientNew = [];
            $clientNew = $clientNewController->index($clientNewData);
        }else{
            $clientNew = [];
        }
        
        if(!isset($clientNew["error"]))
        {
            $clientNewId = $clientNew ? $clientNew->id : null;
            $formAnswers = $this->filterFormAnswer($request->form_id, $filter, $clientNewId);
            $formAnswersData = $formAnswers->getCollection();
            if(count($formAnswersData) == 0)
            {
                $formAnswers = $filterHelper->filterByDataBase($request->form_id, $clientNewId, $filter);
                $formAnswersData = $formAnswers->getCollection();
            }
            if(count($formAnswersData) == 0)
            {
                $formAnswersApi = $filterHelper->filterbyApi($request->form_id, $filter);
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
            
            if($clientNewId) {
                $formAnswer = FormAnswer::where('form_id',$request->form_id)
                ->where('client_new_id', $clientNewId)
                ->latest()->first();
                $data["preloaded"] = $this->preloaded($request->form_id, $clientNewId, $files);
                $data["duplicate_sections"] = !is_null($formAnswer) ?
                $this->checkDuplicateSections($formAnswer->id) : [];
            }

            return response()->json($data, $data['code']);
        }
        return $this->errorResponse('Error al buscar la gestion', 500);
    }

    private function getDataFilters($filters)
    {
        $dataFilters = [];
        $attributes = ["isClientInfo", "preloaded", "client_unique"];
        foreach ($filters as $filter) {
            foreach ($attributes as $attribute) {
                if(isset($filter[$attribute])) {
                    if ($filter[$attribute]) {
                        $dataFilters[$attribute][] = $filter;
                    }
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
            $structureAnswer = $formAnswer['structure_answer'] ? json_decode($formAnswer['structure_answer']) : json_decode($formAnswer['data']);
            $new_structure_answer = array();
            foreach ($structureAnswer as $answer) {
                $fieldId = $answer->id;

                if (isset($answer->duplicated)) {
                    $digitNumbers = explode('_', $answer->key);
                    $digitNumbers = strlen($digitNumbers[count($digitNumbers) - 1]);
                    $fieldId = (int) substr((string) $fieldId, 0, - $digitNumbers);
                }

                if ($this->deletedFieldChecker($formId, $fieldId)){
                    continue;
                }

                $formController = new FormController();
                $select = $formController->findAndFormatValues($formId, $fieldId, $answer->value);

                if($select->valid) {
                    $answer->value = $select->value;
                }

                array_push($new_structure_answer,$answer);

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
            $filterData = json_encode($filterData, JSON_UNESCAPED_UNICODE);
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
                    $fieldSend=[];
                    if(isset($field['duplicated'])){
                        $select = $this->findAndFormatValues($form_answers->form_id, $field['duplicated']['idOriginal'], $field['value']);
                    }else{
                        $select = $this->findAndFormatValues($form_answers->form_id, $field['id'], $field['value']);
                    }
                    $object= new \stdClass();
                    $object->id=$field['id'];
                    array_push($fieldSend,$object);
                    $input = $this->getSpecificFieldForSection($fieldSend,$form_answers->form_id);
                    $field['type']=$input[$object->id]->type;
                    $field['controlType']=$input[$object->id]->controlType;

                    if($select->valid){
                        if(isset($select->name)){
                            $field['value'] = $select->name;
                        }else{
                            $field['value'] = $select->value;
                        }
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
        $date_string = Carbon::now()->toDateTimeString();
        $obj = array();
        $formAnswerData = [];
        $formAnswerIndexData = [];
        $trayFilds = [];
        $data = [];
        $request['sections'] = (gettype($request['sections']) == "string") ? json_decode($request->sections,true):$request->sections;
        foreach ($request['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                if(isset($field["tray"])) {
                    foreach ($field["tray"] as $tray)
                    {
                        $trayExists = $this->validateTrayInSections($tray['id'],$request['form_id']);
                        if($trayExists)
                        {
                            if(trim($field['value']) != ''){
                                array_push($trayFilds, (Object)[
                                    "id"=>$field['id'],
                                    "key"=>$field['key'],
                                    "value"=>$field['value'],
                                    "preloaded"=>$field['preloaded'],
                                    "label"=>$field['label'],
                                    "tray"=>$field['tray']
                                ]);
                                continue;
                            }
                        }
                    }
                }
                $register=[];
                $register['id'] = $field['id'];
                $register['key'] = $field['key'];
                $register['value'] = $field['value'];
                $register['section_id'] = $section['id'];
                $register['preloaded'] = $field['preloaded'];
                $register['label'] = $field['label'];
                $register['isClientInfo'] = isset($field['isClientInfo']) ? $field['isClientInfo'] : false;
                $register['client_unique'] = false;//Campo necesario para procesos de sincronizacion con DataCRM
                //manejo de adjuntos
                if($field['controlType'] == 'file'){
                    if (!empty($request->file($field['id']))) {
                        $createFile = false;
                        //valido que el archivo que se va a cargar no sea igual al que esta almacenado
                        if(!empty($field['value'])){
                            $createFile = true;
                            if(!empty($field['idValue'])){
                                $attachmentExist = $this->existingFile($request->file($field['id'])->get(),$field['idValue']);
                                if($attachmentExist){
                                    $createFile = false;
                                }
                            }
                        }
                        if($createFile){
                            $attachment = new Attachment();
                            $attachment->name = $request->file($field['id'])->getClientOriginalName();
                            $attachment->source = $request->file($field['id'])->store($date_string);
                            $attachment->save();
                            $register['value'] = $attachment->id;
                            $register['nameFile']=$attachment->name; //Agregamos el nombre del archivo para que en el momento de ver las respuestas en el formulario se visualice el nombre.
                        }
                    }
                    if (!empty($field['idValue'])){
                        $register['value'] = $field['idValue'];
                        $register['nameFile'] = $field['nameFile'];
                    }
                }

                if(isset($field['duplicated'])){
                    $register['duplicated']=$field['duplicated'];
                }

                if(!empty($register['value']))
                {
                    array_push($formAnswerData, $register);
                    array_push($formAnswerIndexData, [
                        "id" =>$register["id"],
                        "value" =>$register["value"],
                    ]);
                }
            }
        }
        $form_answer = FormAnswer::where('id', $id)->first();
        $form_answer->structure_answer = json_encode($formAnswerData);
        $form_answer->form_answer_index_data = json_encode($formAnswerIndexData);
        $form_answer->update();
        $clientNewController = new ClientNewController();
        $clientNew = $clientNewController->getClientInfoFromFormAnswers($request->form_id , $obj);

        // Manejar bandejas
        $this->matchTrayFields($form_answer->form_id, $form_answer);

        // ! Por verificar
        //creando el histórico de bandejas
        // if(count($trayFilds) > 0){
        //     $trayHistoricCollection =  collect($trayFilds);
        //     $trayHistoricCollection->each(function ($item, $key) use ($form_answer){
        //         collect($item->tray)->each(function ($tray, $key) use ($form_answer,$item){
        //             $trayId = $tray['id'];
        //             unset($item->tray);
        //             $this->createTrayHistoric($trayId,$form_answer->id,$item);
        //         });
        //     });
        // }

        // Log FormAnswer
        $this->logFormAnswer($form_answer);

        return response()->json('Guardado' ,200);
    }

    /**
     * revisa la bandeja a ver si hay salida o entrada de la gestion a una bandeja
     */
    public function matchTrayFields($formId, $formAnswer){

        $trays = Tray::where('form_id',$formId)->with('trafficConfig')->get();

        foreach ($trays as $tray) {

            /* entrada a bandeja */
            $in_fields_matched = 0;
            foreach(json_decode($tray->fields) as $field){

                $estructura = json_decode($formAnswer->structure_answer);
                // Filtrar que contenga el id del field buscado
                $tray_in = collect($estructura)->filter( function ($value, $key) use ($field) {
                    // si es tipo options, validar el valor del option
                    if($field->type == "options"){
                        if($value->id == $field->id){
                            $validate = false;
                            if(!isset($field->value) || empty($field->value))
                            {
                                return 0;
                            }
                            foreach($field->value as $fieldValue){
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
            if($in_fields_matched > 0 && ((count(json_decode($tray->fields) ) > 0) || ($in_fields_matched == count(json_decode($tray->fields))))){
                if(!$tray->FormAnswers->contains($formAnswer->id)){
                    $formAnswerTrays = FormAnswersTray::create([
                        'form_answer_id' => $formAnswer->id,
                        'tray_id' =>  $tray->id
                    ]);
                    $relUsersTraysModel = new RelTrayUser([
                        'trays_id' => $tray->id,
                        'rrhh_id' => auth()->user()->rrhh_id,
                        'form_answers_trays_id' =>$formAnswerTrays->id
                    ]);
                    $relUsersTraysModel->save();
                    //semaforización
                    if(isset($tray->trafficConfig)){
                        $trafficTrayManager = app(TrafficTrayManager::class);
                        $trafficTrayManager->validateTrafficTrayStatus($formAnswer->id,$tray->trafficConfig);
                    }
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
                if($tray->FormAnswers->contains($formAnswer->id)){
                    //semaforización
                    if(isset($tray->trafficConfig)){
                        $trafficTrayManager = app(TrafficTrayManager::class);
                        $trafficTrayManager->disableTrafficTrayLog($formAnswer->id, $tray->trafficConfig->id);
                    }
                }
                
                $formAnswersTray = FormAnswersTray::where('form_answer_id', $formAnswer->id)
                    ->where('tray_id', $tray->id)->first();
                    
                if (!is_null($formAnswersTray) && isset($formAnswersTray->id)) {
                    $formAnswersTray->delete();
                }
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

    /**
     * @desc Valida los campos precargables de la última respuesta del formulario
     * @param $form_id
     * @param $client_new_id
     * @param $files
     * @return array
     */
    private function preloaded($form_id, $client_new_id, $files)
    {
        $formAnswer = FormAnswer::where('form_id',$form_id)
            ->where('client_new_id', $client_new_id)
            ->latest()->first();

        $directory = Directory::where('form_id',$form_id)
            ->where('client_new_id', $client_new_id)
            ->latest()->first();

        $answer = [];

        if($formAnswer && $directory) {
            $clientData = $formAnswer->structure_answer;

            if(strtotime($directory->updated_at) > strtotime($formAnswer->created_at)){
                $clientData = $directory->data;
            }
        } elseif(!$formAnswer && $directory){
            $clientData = $directory->data;
        } elseif (!$directory && $formAnswer){
            $clientData = $formAnswer->structure_answer;
        }
        
        $structure_data = collect(json_decode($clientData))->filter(function (&$field) use ($form_id){
            $fieldId = $field->id;

            if (isset($field->duplicated)) {
                $digitNumbers = explode('_', $field->key);
                $digitNumbers = strlen($digitNumbers[count($digitNumbers) - 1]);
                $fieldId = (int) substr((string) $fieldId, 0, - $digitNumbers);
            }

            return !$this->deletedFieldChecker($form_id, $fieldId) && $field->preloaded;

        })->toArray();

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

    private function existingFile($file,$idValue):bool{
        $attachment = new Attachment();
        $fileExisting = $attachment->find($idValue);
        if(Storage::get($fileExisting->source) === $file ){
            return true;
        }
        return false;
    }

    /**
     * @desc Crea un registro en la tabla form_answer_trays_historic
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $trayId
     * @param int $formAnswerId
     * @param $structureAnswer
     * @return void
     */
    private function createTrayHistoric(int $trayId,int $formAnswerId,$structureAnswer) : void
    {
        $formAnswersTray = FormAnswersTray::where('form_answer_id', $formAnswerId)
            ->where('tray_id', $trayId)->whereNull('deleted_at')->first();
        if (is_null($formAnswersTray)) {
            $formAnswersTray = FormAnswersTray::create([
                "form_answer_id" => $formAnswerId,
                "tray_id" => $trayId
            ]);
        }
        
        $formAnsersTrayHistoric = FormAnswersTrayHistoric::create([
            "form_answers_trays_id" => $formAnswersTray->id,
            "structure_answer" => json_encode($structureAnswer)
        ]);
    }

    /**
     * @desc Valida si una sección cuenta con el objeto de bandeja.
     * @author Juan Pablo Camargo Vanegas (juan.cv@montechelo.com.co)
     * @param int $trayId: id de la bandeja
     * @param int $formId: id del formulario
     * @return bool
     */
    private function validateTrayInSections(int $trayId,int $formId):bool{
        $exists = false;
        $sections = Section::where('form_id',$formId)->get();
        if($sections){
            foreach ($sections as $section){
                foreach (json_decode($section->fields) as $field){
                    if(isset($field->tray)){
                        foreach ($field->tray as $tray){
                            if($tray->id == $trayId){
                                $exists = true;
                            }
                        }
                    }
                }
            }
        }
        return $exists;
    }

    /**
     * Realiza la consulta sobre los datos precargados de los excel y consulta si el cliente existe,
     * si existe lo actualiza de haberlo solicitado, sino, lo crea.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param mixed $formId
     * @param mixed $uniqueIdentificator
     * @return void
     */
    private function processPreloadedData($formId, $uniqueIdentificator)
    {
        $customerDataPreload = CustomerDataPreload::where('form_id', $formId)
        ->whereJsonContains("unique_identificator",["id" => $uniqueIdentificator->id]);

        $uniqueValueInt = intval($uniqueIdentificator->value);
        
        if (gettype($uniqueValueInt) == 'integer') {
            $customerDataPreload = $customerDataPreload->where(function ($query) use ($uniqueValueInt, $uniqueIdentificator) {
                $query->whereJsonContains("unique_identificator",["value" => $uniqueIdentificator->value])
                ->orWhereJsonContains("unique_identificator",["value" => $uniqueValueInt]);
            });
        } else {
            $customerDataPreload = $customerDataPreload->whereJsonContains("unique_identificator",["value" => $uniqueIdentificator->value]);
        }
        
        $customerDataPreload = $customerDataPreload->first();

        if (!is_null($customerDataPreload)) {
            $clientsManager = new ClientsManager;

            $data = [
                "form_id" => $formId,
                "unique_indentificator" => $customerDataPreload->unique_identificator,
            ];

            $client = $clientsManager->findClient($data);
            $updateExisting = true;

            if(!empty($client) && !$customerDataPreload->to_update){
                $updateExisting = false;
            }
            
            if ($updateExisting) {
                $data['information_data'] = $customerDataPreload->customer_data;
    
                $client = $clientsManager->updateOrCreateClient($data);
    
                if(isset($client->id)){
                    $saveDirectories = $this->addToDirectories($customerDataPreload->form_answer, $formId, $client->id,$customerDataPreload->customer_data);
                    $customerDataPreload->delete();
                }
            }

            if (isset($client->id) && $customerDataPreload->adviser) {
                RelAdvisorClientNew::create([
                    'client_new_id' => $client->id,
                    'rrhh_id' => $customerDataPreload->adviser
                ]);
            }
        }
    }

    /**
     * Crea o actualiza un registro en la tabla directories
     * @author Edwin David Sanchez Babin <e.sanchez@montechelo.com.co> 
     * 
     * @param array $data
     * @param int $formId
     * @param int $clientId
     * @param int $clientNewId
     * @param array $indexForm
     * @return mixed
     */
    private function addToDirectories(array $data,int $formId,int $clientNewId, array $indexForm){
        $newDirectory = Directory::updateOrCreate([
            'form_id' => $formId,
            'client_new_id' => $clientNewId,
            'data' => json_encode($data)

        ],[
            'rrhh_id' => auth()->user()->rrhh_id,
            'form_index' => json_encode($indexForm)
        ]);

        return $newDirectory;
    }

    public function formAnswerFilterFromCeroTray(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric',
            'from_table' => 'required|string'
        ]);

        $miosHelper = new MiosHelper();

        if ($request->from_table == 'CustomerDataPreload') {
            $customerDataPreload = CustomerDataPreload::find($request->id);

            $clientsManager = new ClientsManager;

            $data = [
                "form_id" => $customerDataPreload->form_id,
                "unique_indentificator" => $customerDataPreload->unique_identificator,
            ];

            $client = $clientsManager->findClient($data);
            $updateExisting = true;

            if(!empty($client) && !$customerDataPreload->to_update){
                $updateExisting = false;
            }
            
            if ($updateExisting) {
                $data['information_data'] = $customerDataPreload->customer_data;
    
                $client = $clientsManager->updateOrCreateClient($data);
    
                if(isset($client->id)){
                    $saveDirectories = $this->addToDirectories($customerDataPreload->form_answer, $customerDataPreload->form_id, $client->id, $customerDataPreload->customer_data);
                    $customerDataPreload->delete();
                }
            }

            if (isset($client->id) && $customerDataPreload->adviser) {
                $client = RelAdvisorClientNew::create([
                    'client_new_id' => $client->id,
                    'rrhh_id' => $customerDataPreload->adviser
                ]);
            }
        }
        
        $client = RelAdvisorClientNew::find($request->id)->clientNew;    

        $clientNewId = $client->id;
        $formAnswers = FormAnswer::where('form_id', $client->form_id)
        ->where('client_new_id', $client->id)->paginate(5);
        $formAnswersData = $formAnswers->getCollection();

        if(count($formAnswersData) == 0) {
            $formAnswersData = Directory::where('form_id', $client->form_id)
            ->where('client_new_id', $client->id)->paginate(5);
            $formAnswersData = $formAnswers->getCollection();
        }

        if(count($formAnswersData)) {
            if(!$clientNewId) {
                $clientNewId = $formAnswersData[0]->client_new_id;
            }
            $data = $this->setNewStructureAnswer($formAnswersData, $client->form_id);

            $formAnswersData = $data["formAnswers"];
            $files = $data["files"];
        }

        
        $data = $miosHelper->jsonResponse(true, 200, 'result', $formAnswers);
        
        if($clientNewId) {
            $formAnswer = FormAnswer::where('form_id',$client->form_id)
            ->where('client_new_id', $clientNewId)
            ->latest()->first();
            $data["preloaded"] = $this->preloaded($client->form_id, $clientNewId, $files ?? []);
            $data["duplicate_sections"] = !is_null($formAnswer) ?
            $this->checkDuplicateSections($formAnswer->id) : [];
        }

        return response()->json($data, $data['code']);
    }
}
