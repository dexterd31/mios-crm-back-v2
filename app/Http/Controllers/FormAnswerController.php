<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswer;
use App\Models\Client;
use App\Models\KeyValue;
use App\Models\Directory;
use App\Models\ApiConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\CiuService;
use App\Services\NominaService;
use Helpers\MiosHelper;
use Helpers\FormAnswerHelper;
use Helpers\ApiHelper;
use Helpers\FilterHelper;

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
        try {
            // Se valida si tiene permiso para hacer acciones en formAnswer
            if (Gate::allows('form_answer')) {


                $structure_answer = $formAnswerHelper->structureAnswer($request['form_id'], $request['sections']);

                if ($request['client_id'] == null || $request['client_id'] == "") {
                    $contador = 0;
                    foreach ($request['sections'] as $section) {
                        if ($contador == 0) {
                            $client = new Client([
                                'document_type_id' => !empty($section['document_type_id']) ? $section['document_type_id'] : 1,
                                'first_name' => rtrim($section['firstName']),
                                'middle_name' => rtrim($section['middleName']),
                                'first_lastname' => rtrim($section['lastName']),
                                'second_lastname' => rtrim($section['secondLastName']),
                                'document' => rtrim($section['document']),
                                'phone' => rtrim($section['phone']),
                                'email' => rtrim($section['email'])
                            ]);
                            $client->save();
                        }

                        foreach ($section as $key => $value) {
                            $sect = new KeyValue([
                                'form_id' => $request['form_id'],
                                'client_id' => $client->id,
                                'key' => $key,
                                'value' => $value,
                                'description' => null
                            ]);

                            $sect->save();
                        }
                        $contador++;
                    }

                    $form_answer = new FormAnswer([
                        'user_id' => $request->user_id,
                        'channel_id' => 1,
                        'client_id' => $client->id,
                        'form_id' => $request['form_id'],
                        'structure_answer' => json_encode($structure_answer)
                    ]);

                    $form_answer->save();
                    $message = 'Informacion guardada correctamente';
                } else {
                    foreach ($request['sections'] as $section) {
                        foreach ($section as $key => $value) {
                            $sec = new KeyValue([
                                'form_id' => $request['form_id'],
                                'client_id' => $request['client_id'],
                                'key' => $key,
                                'value' => $value,
                                'description' => null
                            ]);

                            $sec->save();
                        }
                    }
                    $formanswer = new FormAnswer([
                        'user_id' => $request->user_id,
                        'channel_id' => 1,
                        'client_id' => $request['client_id'],
                        'form_id' => $request['form_id'],
                        'structure_answer' => json_encode($structure_answer)
                    ]);

                    $formanswer->save();
                    $message = 'Informacion guardada correctamente';
                }
            } else {
                $message = 'Tú rol no tiene permisos para ejecutar esta acción';
            }
            return $this->successResponse($message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al guardar la gestion', 500);
         }
    }

    /**
     * Olme Marin
     * 26-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function filterForm(Request $request, MiosHelper $miosHelper, FilterHelper $filterHelper, ApiHelper $apiHelper)
    {
        try {
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

                if( $form_answers == null || count($validador['data']) == 0) {
                    // Se busca por api si tiene registrado el formulario
                    $form_answers = $filterHelper->filterbyApi($formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value);
                }

                if ($form_answers != null ) {
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
                    // Cundo se regresa la respuesta vacia porque no incontro registro por ninua fuente de información
                    $arrayData = $apiHelper->responseFilterMios([], $formId);
                    $form_answers = $validador;
                    $form_answers['data'] = [$arrayData];


                }


                $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
            } else {
                $data = $miosHelper->jsonResponse(false, 403, 'message', 'Tú rol no tiene permisos para ejecutar esta acción');
            }
            return response()->json($data, $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al buscar la gestion', 500);
        }
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
        try {
            $form_answers = FormAnswer::where('id', $id)->with('channel', 'client')->paginate(10);
            foreach ($form_answers  as $form) {
                $userData     = $this->ciuService->fetchUser($form->user_id)->data;
                $form->structure_answer = $miosHelper->jsonDecodeResponse($form->structure_answer);
                $form->user = $userData;
            }

            $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
            return response()->json($data, $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al buscar la gestion', 500);
        }
    }
}
