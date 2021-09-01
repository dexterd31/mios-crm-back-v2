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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FormAnswerController extends Controller
{
    private $ciuService;
    private $nominaService;
    private $dataCRMServices;

    public function __construct(CiuService $ciuService, NominaService $nominaService,DataCRMService $dataCRMServices)
    {
        $this->middleware('auth');
        $this->ciuService = $ciuService;
        $this->nominaService = $nominaService;
        $this->dataCRMServices = $dataCRMServices;
    }

    private function create($clientNewId, $formId, $structureAnswer)
    {
        $formsAnswer = new FormAnswer([
            'rrhh_id' => auth()->user()->rrhh_id,
            'channel_id' => 1,
            'form_id' => $formId,
            'structure_answer' => json_encode($structureAnswer),
            'client_new_id' => $clientNewId,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        return $this->saveModel($formsAnswer);
    }

    public function saveinfo(Request $request)
    {
        $sections = json_decode($request['sections'], true);
        $clientNewInfo = [];
        $dataPreloaded = [];
        $formAnswerData = [];
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
                $register['isClientInfo'] = $field['isClientInfo'];
                $register['client_unique'] = false;
                if(isset($field['duplicated']))
                {
                    $register['duplicated']=$field['duplicated'];
                }

                if(isset($field['isClientInfo']) && $field['isClientInfo'])
                {
                    array_push($clientNewInfo, $register);
                    //tener en cuenta guardar label en archivos
                }
                if(!empty($register['value']))
                {
                    if(isset($register['preloaded']) && $register['preloaded'])
                    {
                        array_push($dataPreloaded, $register);
                    }
                    array_push($formAnswerData, $register);
                }
                if(json_decode($request->client_unique)[0]->id == $field['id'])
                {
                    $register['client_unique'] = true;
                    $clientUnique = $register;
                }
            }
        }

        //creando nuevo cliente
        $clientNewController = new ClientNewController();
        $clientNew = new Request();
        $clientNew->replace([
            "client_new_id" => $request->client_new_id,
            "form_id" => $request->form_id,
            "information_data" => json_encode($clientNewInfo),
            "unique_indentificator" => json_encode($clientUnique),
        ]);
        $clientNew = $clientNewController->create($clientNew);

        //creando nuevo cliente formAnswer
        $form_answer = $this->create($clientNew->id, $request->form_id, $formAnswerData);
        $keyValueController = new KeyValueController();
        $keyValueController->createKeysValue($dataPreloaded, $request->form_id, $clientNew->id);

        // Manejar bandejas
        $this->matchTrayFields($form_answer->form_id, $form_answer);
        // Log FormAnswer
        $this->logFormAnswer($form_answer);
        $this->updateDataCrm($clientNew->id, $form_answer);
        return $this->successResponse(['message'=>"Información guardada correctamente",'formAsnwerId'=>$form_answer->id]);
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
            Log::info('FormAnswer ID '.$form_answer->id);
            if($potentialIdObject) $this->dataCRMServices->updatePotentials($form_answer->form_id,json_decode($form_answer->structure_answer),$potentialIdObject->value);
            if($accountIdObject) $this->dataCRMServices->updateAccounts($form_answer->form_id,json_decode($form_answer->structure_answer),$accountIdObject->value);
        }
    }



    /**
     * Nicol Ramirez
     * 11-02-2020
     * Método para guardar la información del formulario
     */
    public function foo(Request $request, MiosHelper $miosHelper, FormAnswerHelper $formAnswerHelper)
    {
         try {
            // Se valida si tiene permiso para hacer acciones en formAnswer
            if (Gate::allows('form_answer')) {
                $now=Carbon::now()->format('Y-m-d H:i:s');
                $json_body = json_decode($request['sections'], true);
                $obj = array();
                $clientInfo = [];
                $clientData = array();
                $i = 0;
                $form_answer = null;
                $date_string = Carbon::now()->format('YmdHis');

                $clientNewInfo = [];
                foreach ($json_body as $section) {
                    foreach ($section['fields'] as $field) {
                        if(isset($field['isClientInfo']) && $field['isClientInfo'])
                        {
                            array_push($clientNewInfo, $field);
                        }

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

                        if(!empty($register['value'])){
                            array_push($obj, $register);
                        }
                    }
                    $i++;
                }
                array_push($clientInfo, $clientData);
                $clientData = array();

                //$request->client_id

                if (is_null($request->client_id) || $request->client_id=='null') {
                //if (json_decode($request['client_id']) == null) {
                    // $clientFind = Client::where('document', $clientInfo[0]['document'])->where('document_type_id', $clientInfo[0]['document_type_id'])->first();

                    // if ($clientFind == null) {
                    //     $client = new Client([
                    //         'document_type_id'  => !empty($clientInfo[0]['document_type_id']) ? $clientInfo[0]['document_type_id'] : 1,
                    //         'first_name'        => isset($clientInfo[0]['firstName']) ? rtrim($clientInfo[0]['firstName']) : '',
                    //         'middle_name'       => isset($clientInfo[0]['middleName']) ? rtrim($clientInfo[0]['middleName']) : '',
                    //         'first_lastname'    => isset($clientInfo[0]['lastName']) ? rtrim($clientInfo[0]['lastName']) : '',
                    //         'second_lastname'   => isset($clientInfo[0]['secondLastName']) ? rtrim($clientInfo[0]['secondLastName']) : '',
                    //         'document'          => isset($clientInfo[0]['document']) ? rtrim($clientInfo[0]['document']) : '',
                    //         'phone'             => isset($clientInfo[0]['phone']) ? rtrim($clientInfo[0]['phone']) : '',
                    //         'email'             => isset($clientInfo[0]['email']) ? rtrim($clientInfo[0]['email']) : ''
                    //     ]);
                    //     $client->save();
                    //     $clientFind = $client;
                    // } else {
                    //     $clientFind->first_name         = isset($clientInfo[0]['firstName']) ? $clientInfo[0]['firstName'] : $clientFind->first_name;
                    //     $clientFind->middle_name        = isset($clientInfo[0]['middleName']) ? $clientInfo[0]['middleName'] : $clientFind->middle_name;
                    //     $clientFind->first_lastname     = isset($clientInfo[0]['lastName']) ? $clientInfo[0]['lastName'] : $clientFind->first_lastname;
                    //     $clientFind->second_lastname    = isset($clientInfo[0]['secondLastName']) ? $clientInfo[0]['secondLastName'] : $clientFind->second_lastname;
                    //     $clientFind->phone              = isset($clientInfo[0]['phone']) ? $clientInfo[0]['phone'] : $clientFind->phone;
                    //     $clientFind->email              = isset($clientInfo[0]['email']) ? $clientInfo[0]['email'] : $clientFind->email;
                    //     $clientFind->update();
                    // }

                    foreach ($obj as $row) {
                        if($row['preloaded'] == true){
                            //Utilizamos el campo description en key values p                                                                                       ara guardar el nombre de un archivo precargable
                            $description=null;
                            if(isset($row['nameFile'])){
                                $description=$row['nameFile'];
                            }
                            $sect = new KeyValue([
                                'form_id' => json_decode($request['form_id']),
                                'client_id' => 1,
                                'key' => $row['key'],
                                'value' => $row['value'],
                                'description' => $description,
                                'field_id' => $row['id'],
                                'client_new_id' => $row['id'],
                            ]);

                            $sect->save();
                        }

                    }
                    // ? es el mismo de la linea 161
                    $form_answer = new FormAnswer([
                        'rrhh_id' => auth()->user()->rrhh_id,
                        'channel_id' => 1,
                        'client_id' => $clientFind == null ? $client->id : $clientFind['id'],
                        'form_id' => json_decode($request['form_id']),
                        'structure_answer' => json_encode($obj)
                    ]);

                    $form_answer->save();
                    $message = 'Información guardada correctamente';
                } else {
                    // $clientFind = Client::where('id', $request->client_id)->first();
                    // $clientFind->first_name         = isset($clientInfo[0]['firstName']) ? $clientInfo[0]['firstName'] : $clientFind->first_name;
                    // $clientFind->middle_name        = isset($clientInfo[0]['middleName']) ? $clientInfo[0]['middleName'] : $clientFind->middle_name;
                    // $clientFind->first_lastname     = isset($clientInfo[0]['lastName']) ? $clientInfo[0]['lastName'] : $clientFind->first_lastname;
                    // $clientFind->second_lastname    = isset($clientInfo[0]['secondLastName']) ? $clientInfo[0]['secondLastName'] : $clientFind->second_lastname;
                    // $clientFind->phone              = isset($clientInfo[0]['phone']) ? $clientInfo[0]['phone'] : $clientFind->phone;
                    // $clientFind->email              = isset($clientInfo[0]['email']) ? $clientInfo[0]['email'] : $clientFind->email;
                    // $clientFind->update();

                    foreach ($obj as $row) {
                        if($row['preloaded'] == true){
                            $sect = new KeyValue([
                                'form_id' => json_decode($request['form_id']),
                                'client_id' => $clientFind['id'],
                                'key' => $row['key'],
                                'value' => $row['value'],
                                'description' => null,
                                'field_id' => $row['id']
                            ]);

                            $sect->save();
                        }
                    }

                    $form_answer = new FormAnswer([
                        'rrhh_id' => auth()->user()->rrhh_id,
                        'channel_id' => 1,
                        'client_id' => json_decode($request['client_id']),
                        'form_id' => json_decode($request['form_id']),
                        'structure_answer' => json_encode($obj)
                    ]);

                    $form_answer->save();
                    $message = 'Información guardada correctamente';
                }

                // Manejar bandejas
                $this->matchTrayFields($form_answer->form_id, $form_answer);
                // Log FormAnswer
                $this->logFormAnswer($form_answer);

                /**
                 * Si el fomulario tiene una integracion con DataCRM entonces la tipificacion será actualizada con DataCRM
                 * @author Carlos Galindez
                 */
                $clientId = $clientFind == null ? $client->id : $clientFind->id;
                $potentialIdObject = KeyValue::where('client_id',$clientId)->where('key','potential-id1')->first(); //Unique ID de Data CRM
                $accountIdObject = KeyValue::where('client_id',$clientId)->where('key','account-id0')->first(); //Unique ID de Data CRM

                if(ApiConnection::where('form_id',$form_answer->form_id)->where('api_type',10)->where('status',1)->first()  ){
                    /**
                     * Codigo Habilitado unicamente para pruebas, mientras DataCRM resuelve el bug
                     */
                    Log::info('FormAnswer ID '.$form_answer->id);
                    if($potentialIdObject) $this->dataCRMServices->updatePotentials($form_answer->form_id,json_decode($form_answer->structure_answer),$potentialIdObject->value);
                    if($accountIdObject) $this->dataCRMServices->updateAccounts($form_answer->form_id,json_decode($form_answer->structure_answer),$accountIdObject->value);

                }

            } else {
                $message = 'Tú rol no tiene permisos para ejecutar esta acción';
            }
            return $this->successResponse(['message'=>$message,'formAsnwerId'=>$form_answer->id]);
         } catch (\Throwable $e) {
             return $this->errorResponse('Error :'.$e->getMessage().' File :'.$e->getFile().' Line :'.$e->getLine(), 500);
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

        $clientNewController = new ClientNewController();
        $clientNeData = new Request();
        $clientNeData->replace([
            'form_id' => $request->form_id,
            "information_data" => $dataFilters["client_unique"],
            "unique_indentificator" => json_encode($dataFilters["client_unique"]),
        ]);
        $clientNew = $clientNewController->index($clientNeData);
        $clientNewId = $clientNew ? $clientNew->id : null;
        $formAnswers = $this->filterFormAnswer($request->form_id, $requestJson['filter'], $clientNewId);
        if(!$formAnswers)
        {
            $formAnswers = $filterHelper->filterByDataBase($request->form_id, $clientNewId, $requestJson['filter']);
        }
        if(!$formAnswers)
        {
            $formAnswers = $filterHelper->filterbyApi($request->form_id, $requestJson['filter']);
        }

        if($formAnswers)
        {
            if(!$clientNewId)
            {
                $clientNewId = $formAnswers[0]->client_new_id;
            }
            $data['preloaded'] = $this->preloaded($request->form_id, $clientNewId);
            $formAnswers = $this->setNewStructureAnswer($formAnswers, $request->form_id);
        }
        
        $data = $miosHelper->jsonResponse(true, 200, 'result', $formAnswers);
        return response()->json($data, $data['code']);
    }

    private function getDataFilters($filters)
    {
        $dataFilters = [];
        $filds = ["isClientInfo", "preloaded", "client_unique"];
        foreach ($filters as $filter)
        {
            foreach ($filds as $fild)
            {
                if($filter[$fild])
                {
                    if(!isset($dataFilters[$fild]))
                    {
                        $dataFilters[$fild] = [];   
                    }
                    $dataFilters[$fild] = $filter;
                }
            }
        }
        return $dataFilters;
    }

    private function setNewStructureAnswer($formAnswers, $formId)
    {
        foreach ($formAnswers as $formAnswer)
        {
            $formAnswer['userdata'] = $this->ciuService->fetchUserByRrhhId($formAnswer['rrhh_id']);
            $structureAnswer = json_decode($formAnswer['structure_answer']);
            foreach ($structureAnswer as $answer) {
                if(!isset($answer->duplicated))
                {
                    $select = $this->findSelect($formId, $answer->id, $answer->value);
                    if($select)
                    {
                        $answer->value = $select;
                        $new_structure_answer[] = $answer;
                    }else
                    {
                        $new_structure_answer[] = $answer;
                    }
                }
            }
            $formAnswer['structure_answer'] = $new_structure_answer;
        }
        return $formAnswers;
    }

    private function filterFormAnswer($formId, $filters, $clientNewId)
    {
        $formAnswersQuery = FormAnswer::where('form_id', $formId);
        foreach ($filters as $filter) {
            Log::info($filter["id"]);
            // $formAnswersQuery = $formAnswersQuery->where('structure_answer->id', $filter["id"])
            //     ->where('structure_answer->value', $filter["value"]);
            $formAnswersQuery = $formAnswersQuery->whereJsonContains('structure_answer', ['id' => $filter["id"]])
                ->whereJsonContains('structure_answer', ['value' => $filter["value"]]);
        }
        if($clientNewId)
        {
            $formAnswersQuery = $formAnswersQuery->where("client_new_id", $clientNewId);
        }
        Log::info($formAnswersQuery->toSql());
        Log::info($formAnswersQuery->get());
        return $formAnswersQuery->paginate(5);
    }

    /**
     * Olme Marin
     * 26-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function foo2(Request $request, MiosHelper $miosHelper, FilterHelper $filterHelper, ApiHelper $apiHelper)
    {
        // try {
            if (Gate::allows('form_answer')) {

                $json_body      = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $request->getContent()), true);
                $formId         = $json_body['form_id'];
                $form_answers   = null;

                $item1key   = !empty($json_body['filter'][0]['key']) ? $json_body['filter'][0]['key'] : '';
                $item1value = !empty($json_body['filter'][0]['value']) ? $json_body['filter'][0]['value'] : '';
                $item2key   = !empty($json_body['filter'][1]['key']) ? $json_body['filter'][1]['key']: '';
                $item2value = !empty($json_body['filter'][1]['value']) ? $json_body['filter'][1]['value'] : '';
                $item3key   = !empty($json_body['filter'][2]['key']) ? $json_body['filter'][2]['key'] : '';
                $item3value = !empty($json_body['filter'][2]['value']) ? $json_body['filter'][2]['value'] : '';

                /*
                * Se busca el si el cliente existe en el sistema
                * Se busca si hay registros en Mios
                */
                $form_answers = $filterHelper->filterByGestions($formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value);
                // Se valida si ya se ha encontrado inforación, sino se busca por id del cliente
                $validador = $miosHelper->jsonDecodeResponse(json_encode($form_answers));
                if ($form_answers == null || count($validador['data']) == 0) {
                    // Se buscan las gestiones por base de datos
                    $clientId = $filterHelper->searchClient($item1value, $item2value, $item3value);
                    /* if ($clientId) {
                        $form_answers = $filterHelper->searchGestionByClientId($formId, $clientId);
                    } */

                    // Se valida si ya se ha encontrado inforación, sino se busca en base de datos
                    $validador = $miosHelper->jsonDecodeResponse(json_encode($form_answers));
                    if ($form_answers == null || count($validador['data']) == 0) {
                        // Se busca por el cargue de base de datos = directory
                        $form_answers = $filterHelper->filterByDataBase($formId, $clientId, $item1value, $item2value, $item3value);
                    }
                }
                // Se valida si ya se ha encontrado inforación, sino se busca si tene api
                $validador = $miosHelper->jsonDecodeResponse(json_encode($form_answers));

                if ($form_answers == null || count($validador['data']) == 0) {
                    // Se busca por api si tiene registrado el formulario
                    $form_answers = $filterHelper->filterbyApi($formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value);
                }

                if ($form_answers != null) {
                    // Se mapea la respuesta
                    foreach ($form_answers as $form) {
                        if (isset($form['structure_answer'])) {
                            $form['structure_answer'] = is_array($form['structure_answer']) ? json_encode($form['structure_answer']) : $form['structure_answer'];
                        }
                        $form['structure_answer'] = isset($form['data']) ? $miosHelper->jsonDecodeResponse($form['data']) : $miosHelper->jsonDecodeResponse($form['structure_answer']);
                        $form['userdata'] = $this->ciuService->fetchUserByRrhhId($form['rrhh_id']);
                        unset($form['data']);

                        $new_structure_answer = [];
                        foreach ($form['structure_answer'] as $value) {
                            if(!isset($value['duplicated'])){
                                $select = $this->findSelect($formId, $value['id'], $value['value']);
                                if($select){
                                    $value['value'] = $select;
                                    $new_structure_answer[] = $value;
                                } else {
                                    $new_structure_answer[] = $value;
                                }
                            }
                        }
                        $form['structure_answer'] = $new_structure_answer;
                    }
                } else {
                    // Cuando se regresa la respuesta vacia porque no incontro registro por ninguna fuente de información
                    $form_answers = $validador;
                    $form_answers['data'] = [];
                }


                $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
                if( !empty($form_answers[0]['client']['id'])){
                    $data['preloaded'] = $this->preloaded($formId, $form_answers[0]['client']['id']);
                }

            } else {
                $data = $miosHelper->jsonResponse(false, 403, 'message', 'Tú rol no tiene permisos para ejecutar esta acción');
            }
            return response()->json($data, $data['code']);
        // } catch (\Throwable $e) {
        //     return $this->errorResponse('Error al buscar la gestion', 500);
        // }
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

    private function preloaded($form_id, $client_id)
    {
        $form = Form::find($form_id);
        $structure_data = [];
        foreach($form->section as $section){
            $section->fields =json_decode($section->fields);
            foreach ( $section->fields as $field) {
                if($field->preloaded == true){
                    //Traemos descripcion pues alli se guarda el nombre del archivo
                    $key_value = KeyValue::where('client_new_id', 1)->where('field_id', $field->id)->select('field_id', 'value', 'key', 'description')->latest()->first();
                    if($key_value){
                        $key_value->id = $key_value->field_id;
                        unset($key_value->field_id);
                        $structure_data[] = $key_value;
                    }
                }
            }
        }

        $answer['data']=$structure_data;
        $answer['client_id']=$client_id;
        return $answer;

    }

    private function findSelect($form_id, $field_id, $value)
    {
        $fields = Section::where('form_id', $form_id)
        ->whereJsonContains('fields', ['id' => $field_id])
        ->first()->fields;

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
