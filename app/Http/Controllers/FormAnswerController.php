<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FormAnswer;
use App\Models\KeyValue;
use App\Models\Section;
use App\Models\Tray;
use App\Models\Attachment;
use App\Models\FormAnswerLog;
use App\Services\CiuService;
use App\Services\NominaService;
use Helpers\ApiHelper;
use Helpers\FilterHelper;
use Helpers\FormAnswerHelper;
use Helpers\MiosHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FormAnswerController extends Controller
{
    private $ciuService;
    private $nominaService;

    public function __construct(CiuService $ciuService, NominaService $nominaService)
    {
        $this->middleware('auth');
        $this->ciuService = $ciuService;
        $this->nominaService = $nominaService;
    }
    /**
     * Nicol Ramirez
     * 11-02-2020
     * Método para guardar la información del formulario
     */
    public function saveinfo(Request $request, MiosHelper $miosHelper, FormAnswerHelper $formAnswerHelper)
    {
        // try {
            // Se valida si tiene permiso para hacer acciones en formAnswer
            if (Gate::allows('form_answer')) {
                $json_body = json_decode($request['sections'], true);
                $obj = array();
                $clientInfo = [];
                $clientData = array();
                $i = 0;
                $form_answer = null;

                $date_string = date('c');
                
                foreach ($json_body as $section) {
                    foreach ($section['fields'] as $field) {
                        if ($i == 0) {
                            $clientData[$field['key']] = $field['value'];
                        }
                        $register['id'] = $field['id'];
                        $register['key'] = $field['key'];
                        $register['value'] = $field['value'];

                        //manejo de adjuntos
                        if($field['controlType'] == 'file'){
                            $register['value'] = $request->file($field['id'])->store($date_string);
                        }

                        array_push($obj, $register);
                    }
                    $i++;
                }

                array_push($clientInfo, $clientData);
                $clientData = array();


                if (json_decode($request['client_id']) == null) {
                    $clientFind = Client::where('document', $clientInfo[0]['document'])->where('document_type_id', $clientInfo[0]['document_type_id'])->first();

                    if ($clientFind == null) {
                        $client = new Client([
                            'document_type_id'  => !empty($clientInfo[0]['document_type_id']) ? $clientInfo[0]['document_type_id'] : 1,
                            'first_name'        => isset($clientInfo[0]['firstName']) ? rtrim($clientInfo[0]['firstName']) : '',
                            'middle_name'       => isset($clientInfo[0]['middleName']) ? rtrim($clientInfo[0]['middleName']) : '',
                            'first_lastname'    => isset($clientInfo[0]['lastName']) ? rtrim($clientInfo[0]['lastName']) : '',
                            'second_lastname'   => isset($clientInfo[0]['secondLastName']) ? rtrim($clientInfo[0]['secondLastName']) : '',
                            'document'          => isset($clientInfo[0]['document']) ? rtrim($clientInfo[0]['document']) : '',
                            'phone'             => isset($clientInfo[0]['phone']) ? rtrim($clientInfo[0]['phone']) : '',
                            'email'             => isset($clientInfo[0]['email']) ? rtrim($clientInfo[0]['email']) : ''
                        ]);
                        $client->save();
                    } else {
                        $clientFind->first_name         = isset($clientInfo[0]['firstName']) ? $clientInfo[0]['firstName'] : $clientFind->first_name;
                        $clientFind->middle_name        = isset($clientInfo[0]['middleName']) ? $clientInfo[0]['middleName'] : $clientFind->middle_name;
                        $clientFind->first_lastname     = isset($clientInfo[0]['lastName']) ? $clientInfo[0]['lastName'] : $clientFind->first_lastname;
                        $clientFind->second_lastname    = isset($clientInfo[0]['secondLastName']) ? $clientInfo[0]['secondLastName'] : $clientFind->second_lastname;
                        $clientFind->phone              = isset($clientInfo[0]['phone']) ? $clientInfo[0]['phone'] : $clientFind->phone;
                        $clientFind->email              = isset($clientInfo[0]['email']) ? $clientInfo[0]['email'] : $clientFind->email;
                        $clientFind->update();
                    }

                    foreach ($obj as $row) {
                        $sect = new KeyValue([
                            'form_id' => json_decode($request['form_id']),
                            'client_id' => $clientFind == null ? $client->id : $clientFind['id'],
                            'key' => $row['key'],
                            'value' => $row['value'],
                            'description' => null
                        ]);

                        $sect->save();
                    }

                    $form_answer = new FormAnswer([
                        'user_id' => json_decode($request['user_id']),
                        'channel_id' => 1,
                        'client_id' => $clientFind == null ? $client->id : $clientFind['id'],
                        'form_id' => json_decode($request['form_id']),
                        'structure_answer' => json_encode($obj)
                    ]);

                    $form_answer->save();
                    $message = 'Informacion guardada correctamente';
                } else {
                    $clientFind = Client::where('id', json_decode($request['client_id']))->first();
                    $clientFind->first_name         = isset($clientInfo[0]['firstName']) ? $clientInfo[0]['firstName'] : $clientFind->first_name;
                    $clientFind->middle_name        = isset($clientInfo[0]['middleName']) ? $clientInfo[0]['middleName'] : $clientFind->middle_name;
                    $clientFind->first_lastname     = isset($clientInfo[0]['lastName']) ? $clientInfo[0]['lastName'] : $clientFind->first_lastname;
                    $clientFind->second_lastname    = isset($clientInfo[0]['secondLastName']) ? $clientInfo[0]['secondLastName'] : $clientFind->second_lastname;
                    $clientFind->phone              = isset($clientInfo[0]['phone']) ? $clientInfo[0]['phone'] : $clientFind->phone;
                    $clientFind->email              = isset($clientInfo[0]['email']) ? $clientInfo[0]['email'] : $clientFind->email;
                    $clientFind->update();

                    foreach ($obj as $row) {
                        $sect = new KeyValue([
                            'form_id' => json_decode($request['form_id']),
                            'client_id' => $clientFind['id'],
                            'key' => $row['key'],
                            'value' => $row['value'],
                            'description' => null
                        ]);

                        $sect->save();
                    }

                    $form_answer = new FormAnswer([
                        'user_id' => json_decode($request['user_id']),
                        'channel_id' => 1,
                        'client_id' => json_decode($request['client_id']),
                        'form_id' => json_decode($request['form_id']),
                        'structure_answer' => json_encode($obj)
                    ]);

                    $form_answer->save();
                    $message = 'Informacion guardada correctamente';
                }

                // Manejar bandejas
                $this->matchTrayFields($form_answer->form_id, $form_answer);
                // Log FormAnswer
                $this->logFormAnswer($form_answer);


            } else {
                $message = 'Tú rol no tiene permisos para ejecutar esta acción';
            }
            return $this->successResponse($message);
        // } catch (\Throwable $e) {
        //     return $this->errorResponse('Error al guardar la gestion', 500);
        // }
    }

    /**
     * Olme Marin
     * 26-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function filterForm(Request $request, MiosHelper $miosHelper, FilterHelper $filterHelper, ApiHelper $apiHelper)
    {
        // try {
            if (Gate::allows('form_answer')) {

                $json_body      = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $request->getContent()), true);
                $formId         = $json_body['form_id'];
                $form_answers   = null;

                $item1key   = !empty($json_body['item1_key']) ? $json_body['item1_key'] : '';
                $item1value = !empty($json_body['item1_value']) ? $json_body['item1_value'] : '';
                $item2key   = !empty($json_body['item2_key']) ? $json_body['item2_key'] : '';
                $item2value = !empty($json_body['item2_value']) ? $json_body['item2_value'] : '';
                $item3key   = !empty($json_body['item3_key']) ? $json_body['item3_key'] : '';
                $item3value = !empty($json_body['item3_value']) ? $json_body['item3_value'] : '';

                /*
                * Se busca el si el cliente existe en el sistema
                * Se busca si hay registros en Mios
                */
                $form_answers = $filterHelper->filterByGestions($formId, $item1value, $item2value, $item3value);

                // Se valida si ya se ha encontrado inforación, sino se busca por id del cliente
                $validador = $miosHelper->jsonDecodeResponse(json_encode($form_answers));

                if ($form_answers == null || count($validador['data']) == 0) {
                    // Se buscan las gestiones por base de datos
                    $clientId = $filterHelper->searchClient($item1value, $item2value, $item3value);

                    if ($clientId) {
                        $form_answers = $filterHelper->searchGestionByClientId($formId, $clientId);
                    }

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
                        $userData = $form['user_id'] != 0 ? $this->ciuService->fetchUser($form['user_id'])->data : 0;
                        $form['structure_answer'] = isset($form['data']) ? $miosHelper->jsonDecodeResponse($form['data']) : $miosHelper->jsonDecodeResponse($form['structure_answer']);
                        $form['userdata'] = $userData;
                        unset($form['data']);
                    }
                } else {
                    // Cuando se regresa la respuesta vacia porque no incontro registro por ninguna fuente de información
                    
                    $form_answers = $validador;
                    $form_answers['data'] = [];
                }


                $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
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
     * Olme Marin
     * 02-03-2021
     * Método para consultar los registro de un cliente en from answer
     */
    public function formAnswerHistoric($id, MiosHelper $miosHelper)
    {
        // try {
            $form_answers = FormAnswer::where('id', $id)->with('channel', 'client')->paginate(10);
            foreach ($form_answers  as $form) {
                $userData     = $this->ciuService->fetchUser($form->user_id)->data;
                $form->structure_answer = $miosHelper->jsonDecodeResponse($form->structure_answer);
                $form->user = $userData;
            }

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
        foreach ($request->sections as $section) {
            foreach ($section['fields'] as $field) {
                if ($i == 0) {
                    $clientData[$field['key']] = $field['value'];
                }
                $register['id'] = $field['id'];
                $register['key'] = $field['key'];
                $register['value'] = $field['value'];
                array_push($obj, $register);
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
                            foreach($field->value as $fieldValue){
                                if($value->value == $fieldValue->id){
                                    return 1;
                                }else{
                                    return 0;
                                }
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
                
                $tray->FormAnswers()->attach($formAnswer->id);
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
                            foreach($field_exit->value as $fieldValue){
                                if($value->value == $fieldValue->id){
                                    return 1;
                                }else{
                                    return 0;
                                }
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
        $log->user_id = $form_answer->user_id;
        $log->save();
    }

    public function downloadFile(Request $request)
    {
        return response()->download(storage_path("app/" . $request->url));
    }
}
